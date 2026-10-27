"""Garvis RAG Server (Flask). Jalankan dengan SATU proses saja (indeks FAISS ada di memori)."""
from __future__ import annotations

import hmac
import logging
from functools import wraps

from flask import Flask, jsonify, request
from werkzeug.exceptions import HTTPException

from config import config
from ingest import IngestError, fetch_all_chunks, ingest_document
from rag_engine import EngineError, InvalidRequest, RAGEngine
from vector_store import IndexStaleError, VectorStoreManager

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
log = logging.getLogger("garvis.server")

app = Flask(__name__)
app.json.ensure_ascii = False

store = VectorStoreManager()
engine = RAGEngine(store)

if not config.API_KEY:
    log.warning("RAG_API_KEY kosong: endpoint tidak diproteksi. Isi sebelum dipakai di luar mesin lokal.")


# ----------------------------------------------------------------------
# Util
# ----------------------------------------------------------------------
def require_key(view):
    @wraps(view)
    def wrapper(*args, **kwargs):
        if config.API_KEY:
            provided = request.headers.get("X-API-Key", "")
            if not hmac.compare_digest(provided, config.API_KEY):
                return jsonify({"success": False, "error": "API key tidak valid."}), 401
        return view(*args, **kwargs)

    return wrapper


def _json_body() -> dict:
    data = request.get_json(silent=True)
    if not isinstance(data, dict):
        raise InvalidRequest("Body harus berupa JSON object.")
    return data


def _opt_int(value, name):
    if value is None:
        return None
    try:
        return int(value)
    except (TypeError, ValueError) as exc:
        raise InvalidRequest(f"{name} harus berupa angka bulat.") from exc


def _opt_float(value, name):
    if value is None:
        return None
    try:
        return float(value)
    except (TypeError, ValueError) as exc:
        raise InvalidRequest(f"{name} harus berupa angka.") from exc


# ----------------------------------------------------------------------
# Error handler
# ----------------------------------------------------------------------
@app.errorhandler(EngineError)
def handle_engine_error(exc: EngineError):
    return jsonify({"success": False, "error": str(exc)}), exc.status


@app.errorhandler(IngestError)
def handle_ingest_error(exc: IngestError):
    return jsonify({"success": False, "error": str(exc)}), 422


@app.errorhandler(IndexStaleError)
def handle_stale(exc: IndexStaleError):
    return jsonify({"success": False, "error": str(exc)}), 409


@app.errorhandler(HTTPException)
def handle_http_error(exc: HTTPException):
    return jsonify({"success": False, "error": exc.description}), exc.code


@app.errorhandler(Exception)
def handle_unexpected(exc: Exception):
    log.exception("Error tak terduga")
    return jsonify({"success": False, "error": "Terjadi kesalahan internal pada RAG server."}), 500


# ----------------------------------------------------------------------
# Endpoint
# ----------------------------------------------------------------------
@app.get("/health")
def health():
    online = engine.ollama_online()
    return jsonify({
        "success": True,
        "status": "ok" if online and not store.needs_rebuild else "degraded",
        "ollama": online,
        "embedding": config.EMBEDDING_SIGNATURE,
        "index": {"vectors": store.count, "needs_rebuild": store.needs_rebuild},
    })


@app.get("/models")
@require_key
def models():
    return jsonify({
        "success": True,
        "default_model": config.DEFAULT_MODEL,
        "models": engine.list_ollama_models(),
    })


@app.post("/ingest")
@require_key
def ingest():
    data = _json_body()
    document_id = _opt_int(data.get("document_id"), "document_id")
    if document_id is None:
        raise InvalidRequest("document_id wajib diisi.")
    result = ingest_document(store, document_id)
    return jsonify({"success": True, **result})


@app.post("/ask")
@require_key
def ask():
    data = _json_body()
    result = engine.ask(
        question=data.get("question"),
        model_name=data.get("model_name"),
        mode=data.get("mode", "qa"),
        history=data.get("history"),
        document_ids=data.get("document_ids"),
        guideline_document_ids=data.get("guideline_document_ids"),
        top_k=_opt_int(data.get("top_k"), "top_k"),
        score_threshold=_opt_float(data.get("score_threshold"), "score_threshold"),
        options=data.get("options"),
    )
    return jsonify({"success": True, **result})


@app.post("/rebuild-index")
@require_key
def rebuild_index():
    vectors = store.rebuild(fetch_all_chunks)
    log.info("Indeks dibangun ulang: %d vektor", vectors)
    return jsonify({"success": True, "vectors": vectors})


@app.post("/reload")
@require_key
def reload_index():
    store.load()
    return jsonify({"success": True, "vectors": store.count, "needs_rebuild": store.needs_rebuild})


if __name__ == "__main__":
    from waitress import serve

    log.info("Garvis RAG Server berjalan di http://%s:%s", config.HOST, config.PORT)
    serve(app, host=config.HOST, port=config.PORT, threads=4, channel_timeout=config.OLLAMA_TIMEOUT + 60)