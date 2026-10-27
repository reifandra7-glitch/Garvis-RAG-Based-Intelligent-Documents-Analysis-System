"""KF-02: parsing, cleaning, chunking, embedding, indexing FAISS + sinkronisasi MySQL."""
from __future__ import annotations

import logging
import re
import subprocess
import tempfile
import unicodedata
from contextlib import contextmanager
from pathlib import Path
from typing import Optional

import pymysql
from docx import Document as DocxDocument
from docx.table import Table
from docx.text.paragraph import Paragraph
from langchain_core.documents import Document
from langchain_text_splitters import RecursiveCharacterTextSplitter
from pypdf import PdfReader

from config import config
from vector_store import VectorStoreManager, make_metadata

log = logging.getLogger("garvis.ingest")

SUPPORTED_EXTENSIONS = {".pdf", ".txt", ".docx", ".doc"}


class IngestError(Exception):
    """Kegagalan pemrosesan dokumen (pesan aman ditampilkan ke pengguna)."""


# ======================================================================
# MySQL
# ======================================================================
@contextmanager
def db_connection():
    conn = pymysql.connect(
        host=config.DB_HOST, port=config.DB_PORT, user=config.DB_USER,
        password=config.DB_PASSWORD, database=config.DB_NAME,
        charset="utf8mb4", cursorclass=pymysql.cursors.DictCursor, autocommit=False,
    )
    try:
        yield conn
    finally:
        conn.close()


def get_document(document_id: int) -> Optional[dict]:
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute(
            "SELECT id, original_name, path, extension, is_guideline FROM documents WHERE id = %s",
            (document_id,),
        )
        return cur.fetchone()


def set_status(document_id: int, status: str, error: Optional[str] = None) -> None:
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute(
            "UPDATE documents SET status = %s, error_message = %s, updated_at = NOW() WHERE id = %s",
            (status, error, document_id),
        )
        conn.commit()


def mark_processed(document_id: int, page_count: int, chunk_count: int) -> None:
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute(
            """UPDATE documents
               SET status = 'processed', error_message = NULL, page_count = %s, chunk_count = %s,
                   processed_at = NOW(), updated_at = NOW()
               WHERE id = %s""",
            (page_count, chunk_count, document_id),
        )
        conn.commit()


def replace_chunks(document_id: int, chunks: list[dict]) -> list[int]:
    """Hapus chunk lama dokumen, sisipkan yang baru, kembalikan ID sesuai urutan chunk_index."""
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute("DELETE FROM chunks WHERE document_id = %s", (document_id,))
        cur.executemany(
            """INSERT INTO chunks
               (document_id, chunk_index, page_number, content, char_count, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, NOW(), NOW())""",
            [
                (document_id, c["chunk_index"], c["page_number"], c["content"], len(c["content"]))
                for c in chunks
            ],
        )
        cur.execute("UPDATE chunks SET vector_id = id WHERE document_id = %s", (document_id,))
        cur.execute(
            "SELECT id, chunk_index FROM chunks WHERE document_id = %s ORDER BY chunk_index",
            (document_id,),
        )
        rows = cur.fetchall()
        conn.commit()
    return [r["id"] for r in rows]


def delete_chunks(document_id: int) -> None:
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute("DELETE FROM chunks WHERE document_id = %s", (document_id,))
        conn.commit()


def fetch_all_chunks() -> list[dict]:
    """Seluruh chunk dari dokumen berstatus processed (untuk /rebuild-index)."""
    with db_connection() as conn, conn.cursor() as cur:
        cur.execute(
            """SELECT c.id, c.document_id, c.chunk_index, c.page_number, c.content,
                      d.original_name, d.is_guideline
               FROM chunks c
               JOIN documents d ON d.id = c.document_id
               WHERE d.status = 'processed'
               ORDER BY c.document_id, c.chunk_index"""
        )
        return list(cur.fetchall())


# ======================================================================
# Parsing
# ======================================================================
def resolve_path(relative: str) -> Path:
    base = config.DOCUMENTS_DIR.resolve()
    candidate = (base / relative).resolve()
    if candidate != base and base not in candidate.parents:
        raise IngestError("Path dokumen tidak valid.")
    if not candidate.is_file():
        raise IngestError(f"File dokumen tidak ditemukan di server: {relative}")
    return candidate


def clean_text(text: str) -> str:
    text = text.replace("\x00", "")
    text = unicodedata.normalize("NFKC", text)
    text = re.sub(r"(\w)-\n(\w)", r"\1\2", text)   # sambung kata yang terpotong tanda hubung
    text = re.sub(r"[ \t\u00a0]+", " ", text)
    text = re.sub(r" *\n *", "\n", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def parse_pdf(path: Path) -> tuple[list[tuple[Optional[int], str]], int]:
    reader = PdfReader(str(path))
    if reader.is_encrypted:
        try:
            if reader.decrypt("") == 0:
                raise IngestError("PDF dilindungi kata sandi.")
        except IngestError:
            raise
        except Exception as exc:
            raise IngestError("PDF dilindungi kata sandi dan tidak dapat dibuka.") from exc

    pages: list[tuple[Optional[int], str]] = []
    for number, page in enumerate(reader.pages, start=1):
        text = clean_text(page.extract_text() or "")
        if text:
            pages.append((number, text))
    if not pages:
        raise IngestError(
            "PDF tidak mengandung teks yang dapat dibaca (kemungkinan hasil scan). OCR belum didukung."
        )
    return pages, len(reader.pages)


def parse_docx(path: Path) -> tuple[list[tuple[Optional[int], str]], int]:
    try:
        document = DocxDocument(str(path))
    except Exception as exc:
        raise IngestError("File DOCX rusak atau tidak dapat dibaca.") from exc

    lines: list[str] = []
    for block in document.iter_inner_content():
        if isinstance(block, Paragraph):
            if block.text.strip():
                lines.append(block.text)
        elif isinstance(block, Table):
            for row in block.rows:
                cells: list[str] = []
                for cell in row.cells:
                    value = cell.text.strip()
                    if value and (not cells or cells[-1] != value):  # lewati sel yang di-merge
                        cells.append(value)
                if cells:
                    lines.append(" | ".join(cells))
    text = clean_text("\n".join(lines))
    if not text:
        raise IngestError("Dokumen DOCX tidak memiliki teks.")
    return [(None, text)], 0   # DOCX tidak memiliki nomor halaman yang pasti


def parse_txt(path: Path) -> tuple[list[tuple[Optional[int], str]], int]:
    raw = path.read_bytes()
    for encoding in ("utf-8-sig", "utf-8", "cp1252", "latin-1"):
        try:
            text = clean_text(raw.decode(encoding))
            break
        except UnicodeDecodeError:
            continue
    else:  # pragma: no cover - latin-1 selalu berhasil
        raise IngestError("Encoding file teks tidak dikenali.")
    if not text:
        raise IngestError("File teks kosong.")
    return [(None, text)], 0


def convert_doc_to_docx(path: Path, outdir: Path) -> Path:
    cmd = [config.LIBREOFFICE_BIN, "--headless", "--convert-to", "docx", "--outdir", str(outdir), str(path)]
    try:
        subprocess.run(cmd, check=True, capture_output=True, timeout=180)
    except FileNotFoundError as exc:
        raise IngestError(
            "Format .doc memerlukan LibreOffice di server (atur LIBREOFFICE_BIN) atau simpan sebagai .docx."
        ) from exc
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as exc:
        raise IngestError("Gagal mengonversi file .doc ke .docx.") from exc
    converted = outdir / (path.stem + ".docx")
    if not converted.exists():
        raise IngestError("Hasil konversi .doc tidak ditemukan.")
    return converted


def parse_file(path: Path) -> tuple[list[tuple[Optional[int], str]], int]:
    ext = path.suffix.lower()
    if ext not in SUPPORTED_EXTENSIONS:
        raise IngestError(f"Format file {ext or '(tanpa ekstensi)'} tidak didukung.")
    if ext == ".pdf":
        return parse_pdf(path)
    if ext == ".docx":
        return parse_docx(path)
    if ext == ".txt":
        return parse_txt(path)
    with tempfile.TemporaryDirectory() as tmp:  # .doc
        return parse_docx(convert_doc_to_docx(path, Path(tmp)))


# ======================================================================
# Chunking
# ======================================================================
def build_chunks(pages: list[tuple[Optional[int], str]]) -> list[dict]:
    splitter = RecursiveCharacterTextSplitter(
        chunk_size=config.CHUNK_SIZE,
        chunk_overlap=config.CHUNK_OVERLAP,
        separators=["\n\n", "\n", ". ", "; ", ", ", " ", ""],
        length_function=len,
    )
    chunks: list[dict] = []
    for page_number, text in pages:
        for piece in splitter.split_text(text):
            piece = piece.strip()
            if len(piece) < config.MIN_CHUNK_CHARS:
                continue
            chunks.append({"chunk_index": len(chunks), "page_number": page_number, "content": piece})
    return chunks


# ======================================================================
# Pipeline utama
# ======================================================================
def ingest_document(store: VectorStoreManager, document_id: int) -> dict:
    row = get_document(document_id)
    if row is None:
        raise IngestError(f"Dokumen #{document_id} tidak ditemukan di database.")

    set_status(document_id, "processing")
    chunks_inserted = False
    try:
        path = resolve_path(row["path"])
        pages, page_count = parse_file(path)
        chunks = build_chunks(pages)
        if not chunks:
            raise IngestError("Tidak ada teks yang dapat diekstrak dari dokumen.")

        store.delete_by_document(document_id)          # aman untuk re-ingest
        chunk_ids = replace_chunks(document_id, chunks)
        chunks_inserted = True

        docs = [
            Document(
                page_content=c["content"],
                metadata=make_metadata(
                    chunk_id=cid, document_id=document_id, document_name=row["original_name"],
                    chunk_index=c["chunk_index"], page=c["page_number"], is_guideline=bool(row["is_guideline"]),
                ),
            )
            for c, cid in zip(chunks, chunk_ids)
        ]
        store.add_documents(docs, [str(cid) for cid in chunk_ids])
        mark_processed(document_id, page_count, len(chunks))
        log.info("Dokumen #%s diproses: %d halaman, %d chunk", document_id, page_count, len(chunks))
        return {"document_id": document_id, "status": "processed", "page_count": page_count, "chunk_count": len(chunks)}

    except Exception as exc:
        log.exception("Gagal memproses dokumen #%s", document_id)
        if chunks_inserted:
            try:
                store.delete_by_document(document_id)
                delete_chunks(document_id)
            except Exception:  # pragma: no cover
                log.exception("Gagal membersihkan sisa chunk dokumen #%s", document_id)
        message = str(exc) if isinstance(exc, IngestError) else f"Gagal memproses dokumen: {exc}"
        set_status(document_id, "failed", message[:1000])
        raise IngestError(message) from exc