"""Konfigurasi terpusat Garvis RAG Server."""
import os
from pathlib import Path

from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent

# Prioritas: environment OS > ai/.env > ../.env (Laravel)
load_dotenv(BASE_DIR / ".env")
load_dotenv(BASE_DIR.parent / ".env")


def _str(name: str, default: str) -> str:
    value = os.getenv(name)
    return default if value is None or value.strip() == "" else value.strip()


def _int(name: str, default: int) -> int:
    value = os.getenv(name)
    return default if value is None or value.strip() == "" else int(value)


def _float(name: str, default: float) -> float:
    value = os.getenv(name)
    return default if value is None or value.strip() == "" else float(value)


def _path(name: str, default: Path) -> Path:
    value = os.getenv(name)
    if value is None or value.strip() == "":
        return default.resolve()
    return Path(value).expanduser().resolve()


class Config:
    # ---- Server ----
    HOST = _str("RAG_HOST", "127.0.0.1")
    PORT = _int("RAG_PORT", 5000)
    API_KEY = _str("RAG_API_KEY", "")

    # ---- Ollama (LLM) ----
    OLLAMA_URL = _str("OLLAMA_URL", "http://localhost:11434").rstrip("/")
    OLLAMA_TIMEOUT = _int("OLLAMA_TIMEOUT", 300)
    DEFAULT_MODEL = _str("OLLAMA_DEFAULT_MODEL", "llama3:8b")
    LLM_TEMPERATURE = _float("LLM_TEMPERATURE", 0.1)
    LLM_NUM_CTX = _int("LLM_NUM_CTX", 8192)

    # ---- Embedding ----
    EMBEDDING_BACKEND = _str("EMBEDDING_BACKEND", "huggingface").lower()
    HF_EMBED_MODEL = _str("HF_EMBED_MODEL", "intfloat/multilingual-e5-large")
    OLLAMA_EMBED_MODEL = _str("OLLAMA_EMBED_MODEL", "nomic-embed-text")
    EMBEDDING_DEVICE = _str("EMBEDDING_DEVICE", "cpu")

    # ---- Chunking ----
    CHUNK_SIZE = _int("CHUNK_SIZE", 1000)
    CHUNK_OVERLAP = _int("CHUNK_OVERLAP", 150)
    MIN_CHUNK_CHARS = _int("MIN_CHUNK_CHARS", 30)

    # ---- Penyimpanan ----
    VECTOR_DIR = _path("VECTOR_DIR", BASE_DIR / "vectorstore")
    INDEX_NAME = "garvis"
    DOCUMENTS_DIR = _path(
        "DOCUMENTS_DIR",
        BASE_DIR.parent / "storage" / "app" / "private"
    )
    LIBREOFFICE_BIN = _str("LIBREOFFICE_BIN", "soffice")

    # ---- Retrieval ----
    TOP_K = _int("RAG_TOP_K", 5)

    # Nilai awal; WAJIB dikalibrasi dengan field retrieval.top_score dari /ask.
    # Skor e5 sempit (relevan ~0.80+, tidak relevan ~0.70-0.78), jadi default-nya tinggi.
    SCORE_THRESHOLD = _float(
        "RAG_SCORE_THRESHOLD",
        0.78 if EMBEDDING_BACKEND == "huggingface" else 0.50
    )

    MAX_CONTEXT_CHARS = _int("MAX_CONTEXT_CHARS", 12000)
    MAX_QUESTION_CHARS = _int("MAX_QUESTION_CHARS", 2000)
    HISTORY_TURNS = _int("HISTORY_TURNS", 4)

    # ---- Compliance (KF-08) ----
    COMPLIANCE_CROSS_QUERIES = _int("COMPLIANCE_CROSS_QUERIES", 4)
    COMPLIANCE_MAX_TARGET = _int("COMPLIANCE_MAX_TARGET", 8)

    # ---- MySQL (sama dengan Laravel) ----
    DB_HOST = _str("DB_HOST", "127.0.0.1")
    DB_PORT = _int("DB_PORT", 3306)
    DB_NAME = _str("DB_DATABASE", "garvis")
    DB_USER = _str("DB_USERNAME", "root")
    DB_PASSWORD = os.getenv("DB_PASSWORD", "")

    @property
    def EMBEDDING_SIGNATURE(self) -> str:
        model = (
            self.HF_EMBED_MODEL
            if self.EMBEDDING_BACKEND == "huggingface"
            else self.OLLAMA_EMBED_MODEL
        )
        return f"{self.EMBEDDING_BACKEND}:{model}"


config = Config()
config.VECTOR_DIR.mkdir(parents=True, exist_ok=True)