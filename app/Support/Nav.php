<?php

namespace App\Support;

use App\Models\ChatRoom;
use App\Models\IncidentReport;
use App\Models\User;

/** Definisi navigasi sidebar per peran + hitungan badge. */
class Nav
{
    /** @return array<int, array{label:string, route:string, icon:string, badge?:string, match?:string}> */
    public static function items(User $u): array
    {
        return match ($u->role?->name) {
            'admin' => [
                ['Dashboard', 'admin.dashboard', 'dashboard', null, 'admin.dashboard'],
                ['Siswa', 'admin.siswa.index', 'users', null, 'admin.siswa.*'],
                ['BK dan Wali Kelas', 'admin.staf.index', 'shield-check', null, 'admin.staf.*'],
                ['Impor akun', 'admin.impor.index', 'file-up', null, 'admin.impor.*'],
                ['Kelas', 'admin.kelas.index', 'school', null, 'admin.kelas.*'],
                ['Role dan akses', 'admin.role.index', 'lock', null, 'admin.role.*'],
                ['Kategori', 'admin.kategori.index', 'tag', null, 'admin.kategori.*'],
                ['Audit log', 'admin.audit.index', 'history', null, 'admin.audit.*'],
                ['Pengaturan', 'admin.pengaturan.index', 'settings', null, 'admin.pengaturan.*'],
            ],
            'bk' => [
                ['Dashboard', 'bk.dashboard', 'dashboard', null, 'bk.dashboard'],
                ['Laporan', 'bk.laporan.index', 'file-text', 'laporan', 'bk.laporan.*|bk.chat.*'],
                ['Konsultasi karir', 'bk.karir.index', 'compass', 'karir', 'bk.karir.*'],
                ['Analitik kata kunci', 'bk.analitik.kata', 'bar-chart', null, 'bk.analitik.*'],
                ['Ringkasan', 'bk.ringkasan', 'gauge', null, 'bk.ringkasan'],
                ['Informasi', 'bk.informasi.index', 'megaphone', null, 'bk.informasi.*'],
                ['Skor kredit', 'bk.skor.index', 'star', null, 'bk.skor.*'],
                ['Pengaturan analitik', 'bk.pengaturan.index', 'settings', null, 'bk.pengaturan.*'],
            ],
            default => [
                ['Dashboard', 'wk.dashboard', 'dashboard', null, 'wk.dashboard'],
                ['Laporan', 'wk.laporan.index', 'file-text', 'laporan', 'wk.laporan.*|wk.chat.*'],
                ['Analitik kata kunci', 'wk.analitik.kata', 'bar-chart', null, 'wk.analitik.*'],
                ['Ringkasan', 'wk.ringkasan', 'gauge', null, 'wk.ringkasan'],
                ['Skor siswa', 'wk.skor.index', 'star', null, 'wk.skor.*'],
                ['Pengaturan analitik', 'wk.pengaturan.index', 'settings', null, 'wk.pengaturan.*'],
            ],
        };
    }

    /** Badge antrean bersama: laporan Baru (semua staf BK/WK) dan sesi karir Menunggu (BK saja). */
    public static function badges(User $u): array
    {
        if (! $u->hasRole('bk', 'wali_kelas')) {
            return [];
        }
        // Wali Kelas: laporan Baru (perlu ditinjau). BK: laporan yang sudah ditinjau dan menunggu diproses (+ yang berisiko).
        $q = IncidentReport::query();
        $u->hasRole('bk')
            ? $q->where(fn ($w) => $w->where('status', 'ditinjau')->orWhere(fn ($x) => $x->where('status', 'baru')->where(fn ($y) => $y->where('risk_flagged', true)->orWhere('ai_flagged', true))))
            : $q->where('status', 'baru');
        $b = [
            'laporan' => $q->count(),
            'laporan_darurat' => (clone $q)->where(fn ($w) => $w->where('risk_flagged', true)->orWhere('ai_flagged', true))->count(),
        ];
        if ($u->hasRole('bk')) {
            $b['karir'] = ChatRoom::where('type', 'karir')->where('status', 'menunggu')->count();
        }
        return $b;
    }
}
