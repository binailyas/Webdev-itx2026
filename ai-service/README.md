# AI Service — klasifikasi prioritas laporan

Sidecar FastAPI yang membungkus model NLP `.pkl` Anda. Laravel memanggilnya lewat HTTP internal;
bila service mati atau lambat (> 2 detik) laporan tetap berjalan normal dan kolom saran AI kosong.

```
Laravel (AiClassifyPriorityJob) ──POST /classify {"text": "judul. kronologi"}──▶ ai-service/app.py ──▶ models/model.pkl
```

## Memasang model Anda

1. Salin file model ke **`ai-service/models/model.pkl`** (atau set `MODEL_PATH`).
2. (Opsional) salin `models/meta.example.json` → `models/meta.json` dan isi versi + metrik. Dipakai untuk
   halaman **Admin → Pengaturan → AI Model** dan tabel `ai_model_versions`.
3. **Samakan versi scikit-learn** dengan lingkungan training (lihat `requirements.txt`). Beda versi = error saat load / hasil salah.
4. Jalankan:

   ```powershell
   cd ai-service
   .\run.ps1            # membuat .venv, install, lalu serve di 127.0.0.1:8001
   ```

5. Cek: `curl http://127.0.0.1:8001/health` → `"model_loaded": true`.
6. Di `.env` Laravel (sudah default): `AI_ENABLED=true`, `AI_ENDPOINT=http://127.0.0.1:8001`.
   Kirim laporan baru → saran muncul di kolom **AI Saran** dan kartu **Saran prioritas otomatis**.

## Kontrak API

| | |
|---|---|
| `POST /classify` | body `{"text": "..."}` → `{"label": "rendah\|sedang\|tinggi", "confidence": 0.0-1.0, "model_version": "...", "meta": {...}}` |
| `GET /health` | `{"status": "ok\|model_missing\|mock", "model_loaded": bool, "model_version": "...", "classes": [...]}` |

* Input hanya **judul + kronologi** (huruf kecil, tanpa HTML). Tidak ada nama/ID/kelas pelapor.
* Teks **tidak pernah dicatat** ke log; hanya label, confidence, dan waktu.
* `confidence ≥ 0.90` **dan** label `tinggi` ⇒ Laravel menandai `ai_flagged` dan memberi notifikasi BK (ambang di `AI_FLAG_THRESHOLD`).
* Label **Darurat** tidak dihasilkan model; itu hanya ditetapkan manusia atau deteksi kata berisiko di Laravel.

## Format `.pkl` yang didukung

Otomatis terdeteksi di `app.py`:

* `sklearn.pipeline.Pipeline` (mis. TF-IDF + LogisticRegression/SVM) — paling umum.
* `dict` berisi `{"model": ..., "vectorizer": ..., "labels": [...]}`.
* Estimator tanpa `predict_proba` (mis. LinearSVC) → skor dari `decision_function` (softmax).

Nama kelas dipetakan lewat tabel `ALIASES` (`low/medium/high`, `0/1/2`, `rendah/sedang/tinggi`, …).
Bila model Anda berformat lain (PyTorch/transformers dsb.), cukup ubah fungsi `_load()` dan `_predict()`.

## Mode uji tanpa model

`.\run.ps1 -Mock` menjalankan heuristik kata kunci (`model_version = "mock-heuristic"`) agar UI bisa dicoba.
**Jangan dipakai di produksi** — itu bukan model.

## Keamanan

* File `.pkl` bisa mengeksekusi kode saat dimuat: hanya muat model buatan sendiri.
* Service bind ke `127.0.0.1`. Jangan ekspos ke publik; tidak ada autentikasi di endpoint ini.
* Folder `models/*.pkl` di-`.gitignore` (jangan commit model ke repo publik).
