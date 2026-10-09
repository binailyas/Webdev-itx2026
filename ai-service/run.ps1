# Menjalankan sidecar AI di http://127.0.0.1:8001  (sesuai AI_ENDPOINT di .env Laravel)
# Pemakaian:  .\run.ps1            -> butuh models\model.pkl
#             .\run.ps1 -Mock      -> mode heuristik untuk uji UI tanpa model (JANGAN produksi)
param([switch]$Mock, [int]$Port = 8001)

Set-Location $PSScriptRoot
if (-not (Test-Path .venv)) {
    python -m venv .venv
    .\.venv\Scripts\python -m pip install --upgrade pip
    .\.venv\Scripts\pip install -r requirements.txt
}
if ($Mock) { $env:ALLOW_MOCK = "1" }
.\.venv\Scripts\python -m uvicorn app:app --host 127.0.0.1 --port $Port
