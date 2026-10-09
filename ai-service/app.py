"""
RuangDengar — sidecar klasifikasi prioritas laporan (M10).

Memuat model NLP berformat .pkl dan membuka dua endpoint untuk Laravel:

    GET  /health    -> status model
    POST /classify  -> {"text": "..."}  =>  {"label": "tinggi", "confidence": 0.91, "model_version": "..."}

Kontrak label: rendah | sedang | tinggi  (Darurat ditentukan manusia / deteksi kata berisiko di Laravel).
Hanya teks laporan (judul + kronologi) yang diterima. Teks TIDAK dicatat ke log.

Format .pkl yang didukung (otomatis terdeteksi):
  1. sklearn Pipeline / estimator yang menerima list[str]   -> predict_proba(texts) / predict(texts)
  2. dict {"model": est, "vectorizer": vec, "labels": [...]} -> est.predict_proba(vec.transform(texts))
  3. objek dengan .predict_proba/.predict dan atribut .classes_
Bila model Anda punya format lain, ubah fungsi `_predict()` di bawah (satu tempat saja).

PERINGATAN KEAMANAN: file .pkl dapat menjalankan kode saat dimuat. Hanya muat model buatan sendiri
dari sumber tepercaya, dan jangan buka endpoint ini ke jaringan publik (bind ke 127.0.0.1).
"""
from __future__ import annotations

import json
import logging
import os
import pickle
import time
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Any

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

BASE = Path(__file__).resolve().parent
MODEL_PATH = Path(os.getenv("MODEL_PATH", BASE / "models" / "model.pkl"))
META_PATH = Path(os.getenv("MODEL_META", BASE / "models" / "meta.json"))
ALLOW_MOCK = os.getenv("ALLOW_MOCK", "0") == "1"  # hanya untuk uji UI tanpa model; JANGAN di produksi

log = logging.getLogger("ai-service")
logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")

LABELS = ("rendah", "sedang", "tinggi")
# Pemetaan nama kelas model -> label sistem. Tambahkan bila model Anda memakai istilah lain.
ALIASES = {
    "rendah": "rendah", "low": "rendah", "ringan": "rendah", "0": "rendah",
    "sedang": "sedang", "medium": "sedang", "menengah": "sedang", "1": "sedang",
    "tinggi": "tinggi", "high": "tinggi", "berat": "tinggi", "2": "tinggi",
}

_state: dict[str, Any] = {"model": None, "vectorizer": None, "classes": None, "meta": {}, "loaded_at": None, "error": None}


def _load() -> None:
    meta: dict[str, Any] = {}
    if META_PATH.exists():
        try:
            meta = json.loads(META_PATH.read_text(encoding="utf-8"))
        except Exception as e:  # noqa: BLE001
            log.warning("meta.json tidak terbaca: %s", e)

    if not MODEL_PATH.exists():
        _state.update(model=None, error=f"File model tidak ditemukan: {MODEL_PATH}")
        log.warning(_state["error"])
        return

    try:
        try:
            import joblib  # lazim dipakai untuk sklearn

            obj = joblib.load(MODEL_PATH)
        except Exception:  # noqa: BLE001
            with open(MODEL_PATH, "rb") as f:
                obj = pickle.load(f)  # noqa: S301 - model tepercaya milik sendiri

        vec = None
        model = obj
        classes = None
        if isinstance(obj, dict) and {"tfidf_word", "tfidf_char", "classifier"} <= obj.keys():
            # Format model_bk.pkl: TF-IDF kata + karakter (hstack) -> LogisticRegression, ambang khusus kelas Tinggi.
            model = obj["classifier"]
            vec = ("bk", obj["tfidf_word"], obj["tfidf_char"], float(obj.get("tinggi_threshold", 0.5)))
            classes = [str(c) for c in obj["label_classes"]]
            m = obj.get("meta") or {}
            meta = {**{
                "f1_score": m.get("macro_f1"), "recall_score": m.get("macro_recall"),
                "catatan": f"Train {m.get('train_n')} / val {m.get('val_n')}; {m.get('split', '')}",
            }, **meta}
            meta = {k: (float(v) if hasattr(v, "item") or isinstance(v, float) else v) for k, v in meta.items() if v is not None}
        elif isinstance(obj, dict):
            model = obj.get("model") or obj.get("clf") or obj.get("pipeline")
            vec = obj.get("vectorizer") or obj.get("tfidf")
            classes = obj.get("labels") or obj.get("classes")
        if model is None:
            raise ValueError("Objek .pkl tidak memuat model (kunci 'model'/'clf'/'pipeline').")
        if classes is None:
            classes = getattr(model, "classes_", None)
            if classes is None and hasattr(model, "steps"):  # Pipeline
                classes = getattr(model.steps[-1][1], "classes_", None)

        _state.update(model=model, vectorizer=vec, classes=list(classes) if classes is not None else None,
                      meta=meta, loaded_at=time.time(), error=None)
        log.info("Model dimuat: %s (kelas=%s)", MODEL_PATH.name, _state["classes"])
    except Exception as e:  # noqa: BLE001
        _state.update(model=None, error=f"Gagal memuat model: {type(e).__name__}: {e}")
        log.error(_state["error"])


def _norm(label: Any) -> str:
    key = str(label).strip().lower()
    if key not in ALIASES:
        raise ValueError(f"Label model '{label}' tidak dikenal. Tambahkan ke ALIASES di app.py.")
    return ALIASES[key]


def _mock(text: str) -> tuple[str, float]:
    """Heuristik kasar HANYA untuk uji antarmuka (ALLOW_MOCK=1)."""
    t = text.lower()
    high = ("dipukul", "dikunci", "ancam", "memar", "senjata", "pisau", "bunuh", "takut ke sekolah", "disentuh", "dipaksa", "diancam")
    mid = ("mengejek", "dikucilkan", "dibentak", "kasar", "menghina", "diejek")
    h = sum(w in t for w in high)
    m = sum(w in t for w in mid)
    if h:
        return "tinggi", min(0.97, 0.80 + 0.06 * h)
    if m:
        return "sedang", min(0.85, 0.62 + 0.05 * m)
    return "rendah", 0.60


def _predict(text: str) -> tuple[str, float]:
    model, vec, classes = _state["model"], _state["vectorizer"], _state["classes"]
    if isinstance(vec, tuple) and vec[0] == "bk":
        from scipy.sparse import hstack

        _, w, c, thr = vec
        x = hstack([w.transform([text]), c.transform([text])]).tocsr()
        proba = model.predict_proba(x)[0]
        names = [_norm(k) for k in classes]
        i_high = names.index("tinggi")
        # Aturan model: bila peluang Tinggi >= ambang terlatih, hasilnya Tinggi (utamakan recall kasus berat).
        idx = i_high if proba[i_high] >= thr else int(proba.argmax())
        return names[idx], float(proba[idx])

    x = [text]
    if vec is not None:
        x = vec.transform(x)

    if hasattr(model, "predict_proba"):
        proba = model.predict_proba(x)[0]
        idx = int(proba.argmax())
        label = classes[idx] if classes is not None else idx
        return _norm(label), float(proba[idx])

    # Tanpa probabilitas (mis. SVM linear): konversi decision_function ke pseudo-probabilitas (softmax).
    if hasattr(model, "decision_function"):
        import numpy as np

        d = np.atleast_1d(model.decision_function(x)[0])
        if d.size == 1:  # biner
            d = np.array([-d[0], d[0]])
        e = np.exp(d - d.max())
        p = e / e.sum()
        idx = int(p.argmax())
        label = classes[idx] if classes is not None else idx
        return _norm(label), float(p[idx])

    return _norm(model.predict(x)[0]), 1.0  # tanpa skor kepercayaan


@asynccontextmanager
async def lifespan(_: FastAPI):
    _load()
    yield


app = FastAPI(title="RuangDengar AI Service", version="1.0.0", lifespan=lifespan)


class ClassifyIn(BaseModel):
    text: str = Field(..., min_length=1, max_length=20000)


@app.get("/health")
def health() -> dict[str, Any]:
    loaded = _state["model"] is not None
    return {
        "status": "ok" if loaded else ("mock" if ALLOW_MOCK else "model_missing"),
        "model_loaded": loaded,
        "model_version": _version(),
        "classes": _state["classes"],
        "error": _state["error"],
    }


def _version() -> str | None:
    meta = _state["meta"] or {}
    return os.getenv("MODEL_VERSION") or meta.get("versi") or meta.get("version") or (MODEL_PATH.stem if _state["model"] is not None else None)


@app.post("/classify")
def classify(body: ClassifyIn) -> dict[str, Any]:
    t0 = time.perf_counter()
    if _state["model"] is None:
        if ALLOW_MOCK:
            label, conf = _mock(body.text)
            return {"label": label, "confidence": round(conf, 4), "model_version": "mock-heuristic", "meta": {"mock": True}}
        raise HTTPException(status_code=503, detail="Model belum dimuat. Letakkan model.pkl di ai-service/models/ lalu restart.")

    try:
        label, conf = _predict(body.text)
    except Exception as e:  # noqa: BLE001
        log.error("Inferensi gagal: %s", type(e).__name__)  # tanpa isi teks
        raise HTTPException(status_code=500, detail=f"Inferensi gagal: {type(e).__name__}") from e

    ms = (time.perf_counter() - t0) * 1000
    log.info("classify label=%s conf=%.2f %.0fms", label, conf, ms)  # hanya hasil + waktu; tanpa teks
    meta = _state["meta"] or {}
    return {
        "label": label,
        "confidence": round(conf, 4),
        "model_version": _version(),
        "meta": {k: meta[k] for k in ("f1_score", "precision_score", "recall_score", "catatan") if k in meta},
    }
