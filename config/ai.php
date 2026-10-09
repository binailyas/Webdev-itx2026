<?php

/*
|--------------------------------------------------------------------------
| Klasifikasi prioritas otomatis (M10)
|--------------------------------------------------------------------------
| Model NLP (.pkl) dijalankan oleh sidecar FastAPI di folder ai-service/.
| Laravel hanya memanggil POST {endpoint}/classify {"text": "..."} lewat HTTP
| internal. Bila endpoint mati/lambat (> timeout), laporan tetap berjalan
| normal dan saran AI dibiarkan kosong (soft-fail).
*/
return [
    'enabled' => (bool) env('AI_ENABLED', true),
    'endpoint' => rtrim((string) env('AI_ENDPOINT', 'http://127.0.0.1:8001'), '/'),
    'timeout' => (float) env('AI_TIMEOUT', 2),
    // confidence >= ambang AND label = tinggi  =>  ai_flagged + notifikasi BK
    'flag_threshold' => (float) env('AI_FLAG_THRESHOLD', 0.90),
];
