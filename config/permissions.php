<?php

/*
|--------------------------------------------------------------------------
| Matriks hak akses (rancangan 2.1) — tampilan untuk Admin (A10).
|--------------------------------------------------------------------------
| Nilai sel: y = boleh · n = tidak · r = baca saja.
| Kolom: siswa, anonim, admin, bk, wali_kelas.
| Sel dikunci sistem (gembok) kecuali yang tercantum pada 'editable', yaitu
| kebijakan sekolah yang benar-benar dibaca aplikasi lewat app_settings.
*/
return [
    'roles' => ['siswa' => 'Siswa', 'anonim' => 'Anonim', 'admin' => 'Admin', 'bk' => 'BK', 'wali_kelas' => 'Wali Kelas'],

    // fitur => [kolom role => setting key]
    'editable' => [
        'laporan.status' => ['wali_kelas' => 'fitur.wk_status'],
        'chat.baca' => ['wali_kelas' => 'fitur.wk_chat'],
        'skor.catat' => ['wali_kelas' => 'fitur.wk_skor'],
    ],

    'groups' => [
        'Laporan' => [
            'laporan.buat' => ['Buat laporan insiden', 'y', 'y', 'n', 'n', 'n'],
            'laporan.lihat_sendiri' => ['Lihat status laporan sendiri', 'y', 'y', 'n', 'n', 'n'],
            'laporan.baca' => ['Baca daftar dan detail laporan', 'n', 'n', 'n', 'y', 'y'],
            'laporan.prioritas' => ['Ubah prioritas laporan', 'n', 'n', 'n', 'y', 'y'],
            'laporan.status' => ['Ubah status laporan', 'n', 'n', 'n', 'y', 'y'],
            'laporan.arsip' => ['Arsipkan laporan', 'n', 'n', 'n', 'y', 'n'],
            'laporan.catatan' => ['Catatan internal untuk BK', 'n', 'n', 'n', 'r', 'y'],
            'laporan.pihak' => ['Tandai pihak terlibat', 'n', 'n', 'n', 'y', 'y'],
        ],
        'Chat' => [
            'chat.insiden' => ['Chat laporan insiden (tulis)', 'y', 'y', 'n', 'y', 'n'],
            'chat.baca' => ['Chat laporan insiden (baca saja)', 'n', 'n', 'n', 'y', 'r'],
            'chat.buka' => ['Buka ruang chat laporan', 'n', 'n', 'n', 'y', 'n'],
        ],
        'Karir' => [
            'karir.chat' => ['Chat konsultasi karir', 'y', 'n', 'n', 'y', 'n'],
            'karir.badge' => ['Badge sesi karir masuk', 'n', 'n', 'n', 'y', 'n'],
        ],
        'Informasi' => [
            'info.baca' => ['Baca informasi BK', 'y', 'y', 'n', 'y', 'n'],
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
        ],
        'AI Saran' => [
            'ai.lihat' => ['Lihat saran prioritas AI', 'n', 'n', 'n', 'y', 'y'],
            'ai.timpa' => ['Timpa saran AI', 'n', 'n', 'n', 'y', 'y'],
        ],
        'Akun' => [
            'akun.kelola' => ['Kelola akun dan peran', 'n', 'n', 'y', 'n', 'n'],
            'akun.impor' => ['Impor massal siswa', 'n', 'n', 'y', 'n', 'n'],
            'akun.naik_kelas' => ['Naik kelas massal', 'n', 'n', 'y', 'n', 'n'],
            'akun.pengaturan' => ['Pengaturan sistem dan AI', 'n', 'n', 'y', 'n', 'n'],
        ],
    ],
];
