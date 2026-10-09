<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien HTTP ke sidecar model NLP (ai-service/).
 * Hanya mengirim judul + kronologi; tanpa nama/ID/kelas pelapor.
 */
class AiClassifier
{
    /** Normalisasi: gabung judul + kronologi, buang HTML, huruf kecil. */
    public static function normalize(string $judul, string $kronologi): string
    {
        $t = strip_tags($judul . '. ' . $kronologi);
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($t)));
    }

    /** @return array{label:string, confidence:float, version:?string}|null  null bila gagal / dimatikan */
    public function classify(string $text): ?array
    {
        if (! config('ai.enabled')) {
            return null;
        }

        try {
            $res = Http::timeout(config('ai.timeout'))->acceptJson()
                ->post(config('ai.endpoint') . '/classify', ['text' => $text]);

            if (! $res->successful()) {
                Log::warning('AI classify: respons ' . $res->status());
                return null;
            }

            $label = strtolower((string) $res->json('label'));
            if (! in_array($label, ['rendah', 'sedang', 'tinggi'], true)) {
                return null;
            }

            return [
                'label' => $label,
                'confidence' => max(0.0, min(1.0, (float) $res->json('confidence'))),
                'version' => $res->json('model_version'),
                'meta' => $res->json('meta'),
            ];
        } catch (Throwable $e) {
            // Log tidak menyimpan isi teks, hanya jenis galat.
            Log::warning('AI classify gagal: ' . class_basename($e));
            return null;
        }
    }

    /** Status endpoint untuk halaman pengaturan admin. */
    public function health(): array
    {
        if (! config('ai.enabled')) {
            return ['ok' => false, 'state' => 'dinonaktifkan'];
        }
        try {
            $r = Http::timeout(1.5)->get(config('ai.endpoint') . '/health');
            return $r->successful() ? ['ok' => true, 'state' => 'aktif'] + (array) $r->json() : ['ok' => false, 'state' => 'tidak tersedia'];
        } catch (Throwable) {
            return ['ok' => false, 'state' => 'tidak tersedia'];
        }
    }
}
