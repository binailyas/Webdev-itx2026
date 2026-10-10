<?php

namespace App\Services;

use App\Models\IncidentReport;
use Illuminate\Support\Str;

/**
 * Ringkasan otomatis satu laporan (G4.1): ekstraktif dan deterministik, bukan model generatif.
 * Menyusun kalimat pembuka, kata kunci teratas, fakta singkat, dan status saran AI.
 * Hanya memakai isi laporan (tanpa identitas pelapor).
 */
class AutoSummary
{
    /** @return array{kalimat:string, kata:array<int,string>, fakta:string, ai:string} */
    public static function make(IncidentReport $r): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($r->kronologi)));
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];

        // Pilih hingga 2 kalimat paling informatif: kalimat pertama + kalimat yang memuat kata kunci terbanyak.
        $kw = $r->keywords()->where('n', 1)->orderByDesc('frekuensi')->limit(8)->pluck('keyword')->all();
        $score = fn ($s) => collect($kw)->filter(fn ($k) => str_contains(mb_strtolower($s), $k))->count();
        $picked = [$sentences[0]];
        if (count($sentences) > 1) {
            $best = collect($sentences)->slice(1)->sortByDesc($score)->first();
            if ($best && $score($best) > 0) {
                $picked[] = $best;
            }
        }
        $kalimat = Str::limit(implode(' ', $picked), 320);

        $fakta = collect([
            $r->category?->name,
            $r->lokasi ? 'di ' . $r->lokasi : null,
            $r->tanggal_kejadian ? $r->tanggal_kejadian->translatedFormat('d M Y') : null,
            ($n = $r->entities()->where('status', '!=', 'ditolak')->count()) ? "$n pihak disebut" : null,
            $r->attachments()->count() ? $r->attachments()->count() . ' lampiran' : null,
        ])->filter()->implode(' · ');

        $ai = $r->ai_priority_suggestion
            ? 'Saran AI: ' . ucfirst($r->ai_priority_suggestion) . ' (' . round(($r->ai_priority_confidence ?? 0) * 100) . '%)' . ($r->risk_flagged ? ' · kata berisiko terdeteksi' : '')
            : 'Analisis AI belum tersedia' . ($r->risk_flagged ? ' · kata berisiko terdeteksi' : '');

        return ['kalimat' => $kalimat, 'kata' => array_slice($kw, 0, 5), 'fakta' => $fakta ?: '—', 'ai' => $ai];
    }
}
