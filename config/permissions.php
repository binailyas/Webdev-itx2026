<?php

/*
|--------------------------------------------------------------------------
| Hak akses per peran (rancangan 2.1) — DEFAULT. Nilai aktif disimpan di tabel
| `role_permissions` dan diedit admin di Role dan akses (A2).
|--------------------------------------------------------------------------
| Nilai sel default: y = boleh · n = tidak · r = baca saja.
| Kolom: siswa, anonim, admin, bk, wali_kelas.
| Sel dapat diedit hanya untuk kolom BK dan Wali Kelas pada fitur yang memang
| bisa dicabut/diberikan ('editable'/'grantable'). Sisanya dikunci sistem
| (struktur privasi, kolom Admin agar admin tidak mengunci dirinya sendiri).
*/
return [
    'roles' => ['siswa' => 'Siswa', 'anonim' => 'Anonim', 'admin' => 'Admin', 'bk' => 'BK', 'wali_kelas' => 'Wali Kelas'],

    // Peran yang dapat diubah admin.
    'editable_roles' => ['bk', 'wali_kelas'],

    // Fitur yang boleh DIBERIKAN walau default 'n' (sudah didukung kode untuk peran itu).
    'grantable' => [
        'analitik.pengaturan' => ['wali_kelas'],
    ],

    // Nama rute staf (tanpa awalan bk./wk.) -> fitur yang diperiksa middleware 'feature'.
    'routes' => [
        'laporan.index' => 'laporan.baca', 'laporan.show' => 'laporan.baca', 'laporan.lampiran' => 'laporan.baca',
        'laporan.status' => 'laporan.status', 'laporan.override' => 'ai.timpa', 'laporan.note' => 'laporan.catatan',
        'laporan.entity' => 'laporan.pihak', 'laporan.arsip' => 'laporan.arsip',
        'chat.show' => 'chat.baca', 'chat.open' => 'chat.buka', 'chat.send' => 'chat.buka', 'chat.allow' => 'chat.buka',
        'ringkasan' => 'analitik.ringkasan', 'ringkasan.unduh' => 'analitik.ringkasan',
        'analitik.kata' => 'analitik.kata', 'analitik.kata.detail' => 'analitik.kata', 'analitik.orang' => 'analitik.kata',
        'analitik.profil' => 'analitik.kata', 'analitik.profil.unduh' => 'analitik.kata', 'analitik.lokasi' => 'analitik.kata',
        'analitik.tren' => 'analitik.kata',
        'analitik.watchlist' => 'analitik.watchlist', 'watchlist.store' => 'analitik.watchlist', 'watchlist.destroy' => 'analitik.watchlist',
        'pengaturan.index' => 'analitik.pengaturan', 'alias.store' => 'analitik.pengaturan', 'alias.destroy' => 'analitik.pengaturan',
        'stopword.store' => 'analitik.pengaturan', 'stopword.destroy' => 'analitik.pengaturan', 'stopword.reset' => 'analitik.pengaturan',
        'pengaturan.ekstraksi' => 'analitik.pengaturan',
        'skor.index' => 'skor.lihat', 'skor.show' => 'skor.lihat', 'skor.store' => 'skor.catat', 'skor.void' => 'skor.batal',
        'karir.index' => 'karir.chat', 'karir.accept' => 'karir.chat', 'karir.show' => 'karir.chat', 'karir.send' => 'karir.chat', 'karir.close' => 'karir.chat',
        'informasi.index' => 'info.kelola', 'informasi.create' => 'info.kelola', 'informasi.store' => 'info.kelola',
        'informasi.edit' => 'info.kelola', 'informasi.update' => 'info.kelola', 'informasi.destroy' => 'info.kelola',
        'arsip.index' => 'arsip.lihat',
    ],

    // fitur => [label, siswa, anonim, admin, bk, wali_kelas]
    'groups' => [
        'Laporan' => [
            'laporan.buat' => ['Buat laporan insiden', 'y', 'y', 'n', 'n', 'n'],
            'laporan.lihat_sendiri' => ['Lihat status laporan sendiri', 'y', 'y', 'n', 'n', 'n'],
            'laporan.baca' => ['Baca daftar dan detail laporan', 'n', 'n', 'n', 'y', 'y'],
            'laporan.status' => ['Ubah status laporan', 'n', 'n', 'n', 'y', 'y'],
            'laporan.arsip' => ['Arsipkan laporan', 'n', 'n', 'n', 'y', 'n'],
            'laporan.catatan' => ['Catatan internal untuk BK', 'n', 'n', 'n', 'y', 'y'],
            'laporan.pihak' => ['Tandai pihak terlibat', 'n', 'n', 'n', 'y', 'y'],
            'arsip.lihat' => ['Lihat arsip kasus', 'n', 'n', 'n', 'y', 'y'],
        ],
        'Chat' => [
            'chat.insiden' => ['Chat laporan insiden (tulis)', 'y', 'y', 'n', 'n', 'n'],
            'chat.baca' => ['Baca chat laporan insiden', 'n', 'n', 'n', 'y', 'r'],
            'chat.buka' => ['Buka ruang dan balas chat laporan', 'n', 'n', 'n', 'y', 'n'],
        ],
        'Karir' => [
            'karir.chat' => ['Chat konsultasi karir', 'y', 'n', 'n', 'y', 'n'],
        ],
        'Informasi' => [
            'info.baca' => ['Baca informasi BK', 'y', 'y', 'n', 'n', 'n'],
            'info.kelola' => ['Kelola informasi', 'n', 'n', 'n', 'y', 'n'],
        ],
        'Skor' => [
            'skor.lihat' => ['Lihat skor dan catatan', 'r', 'n', 'n', 'y', 'r'],
            'skor.catat' => ['Catat pengurangan skor', 'n', 'n', 'n', 'y', 'y'],
            'skor.batal' => ['Batalkan catatan keliru', 'n', 'n', 'n', 'y', 'n'],
        ],
        'Analitik' => [
            'analitik.kata' => ['Analitik kata kunci dan pihak terlibat', 'n', 'n', 'n', 'y', 'y'],
            'analitik.ringkasan' => ['Ringkasan dan unduhan', 'n', 'n', 'n', 'y', 'y'],
            'analitik.watchlist' => ['Kelola watchlist', 'n', 'n', 'n', 'y', 'y'],
            'analitik.pengaturan' => ['Pengaturan analitik (alias, stopword)', 'n', 'n', 'n', 'y', 'n'],
        ],
        'AI Saran' => [
            'ai.lihat' => ['Lihat saran prioritas AI', 'n', 'n', 'n', 'y', 'y'],
            'ai.timpa' => ['Timpa saran AI', 'n', 'n', 'n', 'y', 'y'],
        ],
        'Akun' => [
            'akun.kelola' => ['Kelola akun dan peran', 'n', 'n', 'y', 'n', 'n'],
            'akun.impor' => ['Impor massal siswa', 'n', 'n', 'y', 'n', 'n'],
            'akun.naik_kelas' => ['Naik kelas massal', 'n', 'n', 'y', 'n', 'n'],
            'akun.pengaturan' => ['Pengaturan sistem, skor kredit, dan AI', 'n', 'n', 'y', 'n', 'n'],
        ],
    ],
];
