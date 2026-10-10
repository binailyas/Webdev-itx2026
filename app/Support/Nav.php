<?php

namespace App\Support;

use App\Models\ChatRoom;
use App\Models\IncidentReport;
use App\Models\User;

/** Definisi navigasi sidebar per peran (difilter oleh izin A2) + hitungan badge. */
class Nav
{
    /** @return array<int, array{0:string,1:string,2:string,3:?string,4:string}> [label, rute, ikon, badge, pola-aktif] */
    public static function items(User $u): array
    {
        // Elemen ke-6: fitur yang harus diizinkan agar menu tampil.
        $all = match ($u->role?->name) {
            'admin' => [
                ['Dashboard', 'admin.dashboard', 'dashboard', null, 'admin.dashboard', null],
                ['Siswa', 'admin.siswa.index', 'users', null, 'admin.siswa.*', null],
                ['BK dan Wali Kelas', 'admin.staf.index', 'shield-check', null, 'admin.staf.*', null],
                ['Impor akun', 'admin.impor.index', 'file-up', null, 'admin.impor.*', null],
                ['Kelas', 'admin.kelas.index', 'school', null, 'admin.kelas.*', null],
                ['Role dan akses', 'admin.role.index', 'lock', null, 'admin.role.*', null],
                ['Kategori', 'admin.kategori.index', 'tag', null, 'admin.kategori.*', null],
                ['Audit log', 'admin.audit.index', 'history', null, 'admin.audit.*', null],
                ['Pengaturan', 'admin.pengaturan.index', 'settings', null, 'admin.pengaturan.*', null],
            ],
            'bk' => [
                ['Dashboard', 'bk.dashboard', 'dashboard', null, 'bk.dashboard', null],
                ['Laporan', 'bk.laporan.index', 'file-text', 'laporan', 'bk.laporan.*|bk.chat.*', 'laporan.baca'],
                ['Konsultasi karir', 'bk.karir.index', 'compass', 'karir', 'bk.karir.*', 'karir.chat'],
                ['Arsip kasus', 'bk.arsip.index', 'archive', null, 'bk.arsip.*', 'arsip.lihat'],
                ['Analitik kata kunci', 'bk.analitik.kata', 'bar-chart', null, 'bk.analitik.*', 'analitik.kata'],
                ['Ringkasan', 'bk.ringkasan', 'gauge', null, 'bk.ringkasan', 'analitik.ringkasan'],
                ['Informasi', 'bk.informasi.index', 'megaphone', null, 'bk.informasi.*', 'info.kelola'],
                ['Skor kredit', 'bk.skor.index', 'star', null, 'bk.skor.*', 'skor.lihat'],
                ['Pengaturan analitik', 'bk.pengaturan.index', 'settings', null, 'bk.pengaturan.*', 'analitik.pengaturan'],
            ],
            default => [
                ['Dashboard', 'wk.dashboard', 'dashboard', null, 'wk.dashboard', null],
                ['Laporan', 'wk.laporan.index', 'file-text', 'laporan', 'wk.laporan.*|wk.chat.*', 'laporan.baca'],
                ['Arsip kasus', 'wk.arsip.index', 'archive', null, 'wk.arsip.*', 'arsip.lihat'],
                ['Analitik kata kunci', 'wk.analitik.kata', 'bar-chart', null, 'wk.analitik.*', 'analitik.kata'],
                ['Ringkasan', 'wk.ringkasan', 'gauge', null, 'wk.ringkasan', 'analitik.ringkasan'],
                ['Skor siswa', 'wk.skor.index', 'star', null, 'wk.skor.*', 'skor.lihat'],
                ['Pengaturan analitik', 'wk.pengaturan.index', 'settings', null, 'wk.pengaturan.*', 'analitik.pengaturan'],
            ],
        };

        return array_values(array_map(
            fn ($i) => array_slice($i, 0, 5),
            array_filter($all, fn ($i) => $i[5] === null || Perm::allows($u, $i[5]))
        ));
    }

    /** Badge antrean bersama: laporan (WK: Baru; BK: sudah ditinjau menunggu diproses) dan sesi karir Menunggu (BK saja). */
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
