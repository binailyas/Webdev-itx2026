<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Membangun ERD (Mermaid) langsung dari skema database yang sedang berjalan,
 * sehingga dokumen selalu sama dengan migrasi.  php artisan docs:erd
 */
class GenerateErd extends Command
{
    protected $signature = 'docs:erd';
    protected $description = 'Hasilkan docs/ERD.md dan docs/erd.html dari skema database';

    /** Domain => [tabel milik domain (kolom penuh), tabel rujukan (hanya PK)]. */
    private const DOMAINS = [
        '1 — Pengguna & Autentikasi' => [['roles', 'users', 'student_profiles', 'classrooms', 'wali_kelas_assignments', 'anonymous_accounts'], []],
        '2 — Laporan Insiden' => [['incident_categories', 'incident_reports', 'report_attachments', 'report_status_histories', 'report_notes', 'report_keywords', 'report_entities'], ['users', 'anonymous_accounts']],
        '3 — AI Klasifikasi Prioritas' => [['ai_model_versions', 'ai_overrides'], ['incident_reports', 'users']],
        '4 — Chat' => [['chat_rooms', 'chat_participants', 'chat_messages'], ['incident_reports', 'users', 'anonymous_accounts']],
        '5 — Analitik Kata Kunci' => [['keyword_aliases', 'keyword_stopwords', 'keyword_daily_stats', 'watchlist_terms'], ['users', 'incident_categories']],
        '6 — Skor Kredit, Konten & Sistem' => [['credit_categories', 'credit_records', 'announcements', 'notifications', 'audit_logs', 'app_settings'], ['users', 'incident_reports', 'anonymous_accounts']],
    ];

    /** Tabel framework Laravel yang tidak ikut digambar. */
    private const SKIP = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens'];

    public function handle(): int
    {
        $tables = collect(Schema::getTables(DB::getDatabaseName()))->pluck('name')->reject(fn ($t) => in_array($t, self::SKIP, true))->sort()->values();

        $cols = [];
        $fks = [];
        foreach ($tables as $t) {
            $fkCols = [];
            foreach (Schema::getForeignKeys($t) as $fk) {
                foreach ($fk['columns'] as $c) {
                    $fkCols[$c] = true;
                }
                $fks[] = ['from' => $t, 'cols' => $fk['columns'], 'to' => $fk['foreign_table']];
            }
            $uniq = collect(Schema::getIndexes($t))->filter(fn ($i) => $i['unique'] && ! $i['primary'] && count($i['columns']) === 1)->pluck('columns.0')->flip();
            foreach (Schema::getColumns($t) as $c) {
                $cols[$t][] = [
                    'name' => $c['name'], 'type' => $this->type($c['type_name'] ?? $c['type']),
                    'key' => $c['name'] === 'id' ? 'PK' : (isset($fkCols[$c['name']]) ? 'FK' : (isset($uniq[$c['name']]) ? 'UK' : '')),
                    'null' => $c['nullable'],
                ];
            }
        }

        $full = $this->diagram($tables->all(), $cols, $fks, false);
        $md = "# ERD — Sistem Layanan Terpadu BK Sekolah\n\n";
        $md .= "> Dihasilkan otomatis dari skema database (`php artisan docs:erd`) — {$tables->count()} tabel aplikasi. "
            . "Tabel bawaan framework (`cache`, `jobs`, `sessions`, dst.) tidak digambar.\n\n";
        $md .= "Legenda: **PK** primary key · **FK** foreign key · **UK** unique. Relasi `||--o{` = satu-ke-banyak, `}o--o|` = banyak-ke-satu opsional.\n\n";
        $md .= "## Gambaran menyeluruh (relasi, tanpa kolom)\n\n```mermaid\n" . $this->diagram($tables->all(), $cols, $fks, true) . "```\n\n";

        foreach (self::DOMAINS as $name => [$own, $ref]) {
            $own = array_values(array_filter($own, fn ($t) => $tables->contains($t)));
            $set = array_merge($own, array_values(array_filter($ref, fn ($t) => $tables->contains($t))));
            $md .= "## Domain $name\n\n```mermaid\n" . $this->diagram($set, $cols, $fks, false, $own) . "```\n\n";
        }
        $md .= "## Kamus tabel\n\n";
        foreach ($tables as $t) {
            $md .= "### `$t`\n\n| Kolom | Tipe | Kunci | Null |\n|---|---|---|---|\n";
            foreach ($cols[$t] as $c) {
                $md .= "| {$c['name']} | {$c['type']} | {$c['key']} | " . ($c['null'] ? 'ya' : '') . " |\n";
            }
            $md .= "\n";
        }

        File::ensureDirectoryExists(base_path('docs'));
        File::put(base_path('docs/ERD.md'), $md);
        File::put(base_path('docs/erd.mmd'), $full);
        File::put(base_path('docs/erd.html'), $this->html($full, $tables->count()));

        $this->info("ERD dibuat: {$tables->count()} tabel, " . count($fks) . ' relasi -> docs/ERD.md, docs/erd.mmd, docs/erd.html');
        return self::SUCCESS;
    }

    private function type(string $t): string
    {
        return match (true) {
            str_contains($t, 'bigint') => 'bigint', str_contains($t, 'tinyint') => 'tinyint', str_contains($t, 'int') => 'int',
            in_array($t, ['varchar', 'char'], true) => 'string', in_array($t, ['text', 'longtext', 'mediumtext'], true) => 'text',
            str_contains($t, 'timestamp') || str_contains($t, 'datetime') => 'timestamp', $t === 'enum' => 'enum',
            default => $t,
        };
    }

    /** @param array<int,string> $names @param array<int,string> $primary tabel yang kolomnya ditampilkan penuh */
    private function diagram(array $names, array $cols, array $fks, bool $compact, ?array $primary = null): string
    {
        $out = "erDiagram\n";
        $show = $primary ?? $names;
        foreach ($names as $t) {
            if ($compact) {
                $out .= "    $t\n";
                continue;
            }
            $out .= "    $t {\n";
            if (in_array($t, $show, true)) {
                foreach ($cols[$t] as $c) {
                    $out .= "        {$c['type']} {$c['name']}" . ($c['key'] ? " {$c['key']}" : '') . "\n";
                }
            } else {
                $out .= "        bigint id PK\n";
            }
            $out .= "    }\n";
        }
        $seen = [];
        foreach ($fks as $fk) {
            if (! in_array($fk['from'], $names, true) || ! in_array($fk['to'], $names, true)) {
                continue;
            }
            $nullable = collect($cols[$fk['from']])->firstWhere('name', $fk['cols'][0])['null'] ?? false;
            $key = "{$fk['to']}>{$fk['from']}>{$fk['cols'][0]}";
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rel = $nullable ? '|o--o{' : '||--o{';
            $out .= "    {$fk['to']} $rel {$fk['from']} : \"{$fk['cols'][0]}\"\n";
        }
        return $out;
    }

    private function html(string $diagram, int $n): string
    {
        $d = e($diagram);
        return <<<HTML
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ERD · BK Sahabat</title>
<style>body{font-family:system-ui,sans-serif;margin:0;background:#FAF9FF;color:#26214A}header{padding:16px 24px;border-bottom:2px solid #DAD6F2;background:#fff}h1{margin:0;font-size:20px}p{margin:4px 0 0;color:#625E85;font-size:13px}main{padding:16px;overflow:auto}.mermaid{min-width:1400px}</style></head>
<body><header><h1>ERD — Sistem Layanan Terpadu BK Sekolah</h1><p>$n tabel · dihasilkan dari skema database (php artisan docs:erd) · butuh internet untuk memuat Mermaid</p></header>
<main><pre class="mermaid">$d</pre></main>
<script type="module">import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10.9.1/dist/mermaid.esm.min.mjs';mermaid.initialize({startOnLoad:true,theme:'base',themeVariables:{primaryColor:'#ECE9FD',primaryBorderColor:'#6A5AE0',lineColor:'#6A5AE0',fontFamily:'system-ui'},er:{useMaxWidth:false}});</script>
</body></html>
HTML;
    }
}
