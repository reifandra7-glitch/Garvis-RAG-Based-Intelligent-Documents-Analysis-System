"""Pengelola FAISS + embedding, dipakai bersama oleh ingest.py dan rag_engine.py.

Skor: vektor dinormalisasi (L2) dan indeks memakai jarak L2 kuadrat,
sehingga cosine = 1 - jarak/2.
"""
from __future__ import annotations

import json
import logging
import threading
from typing import Callable, Optional

import numpy as np
from langchain_community.vectorstores import FAISS
from langchain_core.documents import Document
from langchain_core.embeddings import Embeddings

from config import config

log = logging.getLogger("garvis.vector")

EMBED_BATCH = 32


class IndexStaleError(RuntimeError):
    """Indeks dibuat dengan model embedding lain dan harus dibangun ulang."""


class NormalizedEmbeddings(Embeddings):
    """Pembungkus embedding: prefix model (e5/nomic), batching, dan normalisasi L2."""

    def __init__(self, base: Embeddings, doc_prefix: str = "", query_prefix: str = ""):
        self.base = base
        self.doc_prefix = doc_prefix
        self.query_prefix = query_prefix

    @staticmethod
    def _normalize(vectors) -> list[list[float]]:
        arr = np.asarray(vectors, dtype="float32")
        norms = np.linalg.norm(arr, axis=1, keepdims=True)
        norms[norms == 0] = 1.0
        return (arr / norms).tolist()

    def embed_documents(self, texts: list[str]) -> list[list[float]]:
        out: list[list[float]] = []
        for i in range(0, len(texts), EMBED_BATCH):
            batch = [self.doc_prefix + t for t in texts[i : i + EMBED_BATCH]]
            out.extend(self._normalize(self.base.embed_documents(batch)))
        return out

    def embed_query(self, text: str) -> list[float]:
        return self._normalize([self.base.embed_query(self.query_prefix + text)])[0]


def build_embeddings() -> NormalizedEmbeddings:
    if config.EMBEDDING_BACKEND == "ollama":
        from langchain_ollama import OllamaEmbeddings

        name = config.OLLAMA_EMBED_MODEL
        base = OllamaEmbeddings(model=name, base_url=config.OLLAMA_URL)
    else:
        from langchain_huggingface import HuggingFaceEmbeddings

        name = config.HF_EMBED_MODEL
        base = HuggingFaceEmbeddings(
            model_name=name,
            model_kwargs={"device": config.EMBEDDING_DEVICE},
            encode_kwargs={"normalize_embeddings": True},
        )

    lowered = name.lower()
    if "e5" in lowered:
        doc_prefix, query_prefix = "passage: ", "query: "
    elif "nomic-embed-text" in lowered:
        doc_prefix, query_prefix = "search_document: ", "search_query: "
    else:
        doc_prefix, query_prefix = "", ""

    log.info("Embedding aktif: %s (prefix doc=%r query=%r)", config.EMBEDDING_SIGNATURE, doc_prefix, query_prefix)
    return NormalizedEmbeddings(base, doc_prefix, query_prefix)


def make_metadata(*, chunk_id: int, document_id: int, document_name: str,
                  chunk_index: int, page: Optional[int], is_guideline: bool) -> dict:
    return {
        "chunk_id": int(chunk_id),
        "document_id": int(document_id),
        "document_name": document_name,
        "chunk_index": int(chunk_index),
        "page": int(page) if page is not None else None,
        "is_guideline": bool(is_guideline),
    }


class VectorStoreManager:
    def __init__(self):
        self.embeddings = build_embeddings()
        self._writer = threading.RLock()  # serialisasi ingest / delete / rebuild
        self._lock = threading.RLock()    # melindungi akses ke objek FAISS
        self._store: Optional[FAISS] = None
        self.needs_rebuild = False
        self.load()

    # ---------- path ----------
    @property
    def _faiss_file(self):
        return config.VECTOR_DIR / f"{config.INDEX_NAME}.faiss"

    @property
    def _pkl_file(self):
        return config.VECTOR_DIR / f"{config.INDEX_NAME}.pkl"

    @property
    def _meta_file(self):
        return config.VECTOR_DIR / f"{config.INDEX_NAME}.meta.json"

    @property
    def count(self) -> int:
        with self._lock:
            return 0 if self._store is None else int(self._store.index.ntotal)

    # ---------- load / save ----------
    def load(self) -> None:
        with self._writer, self._lock:
            self._store = None
            self.needs_rebuild = False
            if not self._faiss_file.exists():
                log.info("Belum ada indeks FAISS; akan dibuat saat dokumen pertama diproses.")
                return
            if self._meta_file.exists():
                meta = json.loads(self._meta_file.read_text(encoding="utf-8"))
                if meta.get("signature") != config.EMBEDDING_SIGNATURE:
                    self.needs_rebuild = True
                    log.warning(
                        "Indeks dibuat dengan %s tetapi konfigurasi sekarang %s. Jalankan POST /rebuild-index.",
                        meta.get("signature"), config.EMBEDDING_SIGNATURE,
                    )
                    return
            # File pickle ini dibuat oleh server sendiri, jadi aman dimuat.
            self._store = FAISS.load_local(
                str(config.VECTOR_DIR), self.embeddings,
                index_name=config.INDEX_NAME, allow_dangerous_deserialization=True,
            )
            log.info("Indeks FAISS dimuat: %d vektor", self._store.index.ntotal)

    def _save(self) -> None:
        """Panggil hanya saat memegang self._lock."""
        config.VECTOR_DIR.mkdir(parents=True, exist_ok=True)
        self._store.save_local(str(config.VECTOR_DIR), index_name=config.INDEX_NAME)
        self._meta_file.write_text(
            json.dumps({"signature": config.EMBEDDING_SIGNATURE, "vectors": int(self._store.index.ntotal)}),
            encoding="utf-8",
        )

    # ---------- tulis ----------
    def add_documents(self, docs: list[Document], ids: list[str]) -> None:
        if not docs:
            return
        with self._writer:
            if self.needs_rebuild:
                raise IndexStaleError(
                    "Model embedding berubah sejak indeks dibuat. Jalankan POST /rebuild-index terlebih dahulu."
                )
            texts = [d.page_content for d in docs]
            vectors = self.embeddings.embed_documents(texts)  # lambat, di luar self._lock
            pairs = list(zip(texts, vectors))
            metadatas = [d.metadata for d in docs]
            with self._lock:
                if self._store is None:
                    self._store = FAISS.from_embeddings(pairs, self.embeddings, metadatas=metadatas, ids=ids)
                else:
                    self._store.add_embeddings(pairs, metadatas=metadatas, ids=ids)
                self._save()

    def delete_by_document(self, document_id: int) -> int:
        with self._writer, self._lock:
            if self._store is None:
                return 0
            ids = [
                doc_id for doc_id, doc in self._store.docstore._dict.items()
                if doc.metadata.get("document_id") == document_id
            ]
            if ids:
                self._store.delete(ids)
                self._save()
            return len(ids)

    def rebuild(self, fetch_rows: Callable[[], list[dict]]) -> int:
        """Bangun ulang seluruh indeks dari baris chunks di MySQL (re-embed semua)."""
        with self._writer:
            rows = fetch_rows()
            if not rows:
                with self._lock:
                    self._store = None
                    self.needs_rebuild = False
                    for f in (self._faiss_file, self._pkl_file, self._meta_file):
                        f.unlink(missing_ok=True)
                return 0

            texts = [r["content"] for r in rows]
            metadatas = [
                make_metadata(
                    chunk_id=r["id"], document_id=r["document_id"], document_name=r["original_name"],
                    chunk_index=r["chunk_index"], page=r["page_number"], is_guideline=bool(r["is_guideline"]),
                )
                for r in rows
            ]
            ids = [str(r["id"]) for r in rows]
            vectors = self.embeddings.embed_documents(texts)
            new_store = FAISS.from_embeddings(
                list(zip(texts, vectors)), self.embeddings, metadatas=metadatas, ids=ids
            )
            with self._lock:
                self._store = new_store
                self.needs_rebuild = False
                self._save()
            return len(rows)

    # ---------- baca ----------
    @staticmethod
    def _to_cosine(distance: float) -> float:
        return max(0.0, min(1.0, 1.0 - float(distance) / 2.0))

    def search(self, query: str, k: int,
               predicate: Optional[Callable[[dict], bool]] = None) -> list[tuple[Document, float]]:
        """Mengembalikan [(Document, cosine)] terurut dari yang paling mirip."""
        vector = self.embeddings.embed_query(query)
        with self._lock:
            if self._store is None or self._store.index.ntotal == 0:
                return []
            total = int(self._store.index.ntotal)
            raw = self._store.similarity_search_with_score_by_vector(
                vector, k=k, filter=predicate, fetch_k=total if predicate else k
            )
        results = [(doc, self._to_cosine(dist)) for doc, dist in raw]
        results.sort(key=lambda item: item[1], reverse=True)
        return results