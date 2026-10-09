<?php

namespace App\Services;

/**
 * Asisten RuangDengar (T1): chatbot berbasis aturan untuk pertanyaan dasar dan konsultasi sederhana.
 * BUKAN model bahasa dan bukan pengganti guru BK; selalu menawarkan penerusan ke BK.
 * Tidak menyimpan percakapan dan tidak menerima identitas pengguna.
 */
class Chatbot
{
    private const RISK = ['bunuh diri', 'mengakhiri hidup', 'ingin mati', 'menyakiti diri', 'melukai diri', 'tidak ingin hidup', 'pengen mati', 'mau mati'];

    /** @return array{text:string, actions:array<int,array{label:string,url:string}>, risk:bool} */
    public static function reply(string $message, bool $anon): array
    {
        $m = ' ' . mb_strtolower(trim(preg_replace('/\s+/u', ' ', $message))) . ' ';
        $has = fn (array $words) => (bool) array_filter($words, fn ($w) => str_contains($m, $w));

        $report = ['label' => 'Buat laporan', 'url' => route('siswa.laporan.create')];
        $status = ['label' => 'Cek status laporan', 'url' => route('siswa.cekstatus')];
        $help = ['label' => 'Bantuan darurat', 'url' => route('darurat')];

        if ($has(self::RISK)) {
            return ['risk' => true, 'actions' => [$help, $report], 'text' =>
                "Terima kasih sudah berani menulis ini. Keselamatanmu yang paling penting. Kalau kamu dalam bahaya sekarang, hubungi 112 atau SAPA 129. "
                . "Kamu juga bisa langsung bicara dengan guru BK; mereka akan menjaga ceritamu."];
        }
        if ($has([' halo', ' hai ', ' hi ', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'assalamualaikum'])) {
            return self::r("Hai, aku asisten RuangDengar. Aku bisa menjawab pertanyaan dasar atau menemanimu bercerita sebentar. Kalau perlu, aku teruskan ke guru BK. Mau tanya apa?", [$report, $status]);
        }
        if ($has(['anonim', 'tanpa nama', 'identitas', 'rahasia', 'privasi', 'aman'])) {
            return self::r("Kamu bisa lapor tanpa nama: sistem membuat alias acak dan kata sandi sementara, tanpa menyimpan nama atau kelasmu. Laporan hanya dilihat guru BK dan wali kelas. Simpan alias dan kata sandinya, karena tidak bisa dipulihkan.", $anon ? [$report] : [['label' => 'Pelajari lapor anonim', 'url' => route('anon.info')], $report]);
        }
        if ($has(['status', 'tiket', 'pin ', 'sudah diproses', 'sampai mana'])) {
            return self::r("Kamu bisa melihat status di menu Laporan, atau cek dengan kode tiket dan PIN 6 digit yang muncul saat laporan terkirim. Status berjalan: Baru → Ditinjau → Diproses → Selesai.", [$status, ['label' => 'Laporan saya', 'url' => route('siswa.laporan.index')]]);
        }
        if ($has(['lapor', 'melapor', 'mengadu', 'buat laporan', 'cara report'])) {
            return self::r("Caranya: pilih Buat laporan, pilih kategori, ceritakan kejadiannya, tambahkan bukti bila ada, lalu kirim. Kamu dapat kode tiket dan PIN. Tulis sebanyak yang kamu nyaman bagikan.", [$report]);
        }
        if ($has(['dibully', 'dibuli', 'di-bully', 'bully', 'perundungan', 'dipukul', 'diejek', 'dikucilkan', 'diancam', 'dilecehkan', 'diganggu'])) {
            return self::r("Maaf kamu mengalami itu, dan itu bukan salahmu. Kamu bisa menceritakannya lewat laporan (boleh tanpa nama) supaya guru BK bisa membantu. Kalau ada bukti seperti tangkapan layar, simpan dulu. Kalau kamu sedang tidak aman, pergi ke tempat ramai dan cari guru terdekat.", [$report, $help]);
        }
        if ($has(['sedih', 'stres', 'stress', 'cemas', 'takut', 'khawatir', 'capek', 'lelah', 'sendirian', 'kesepian', 'tertekan', 'overthinking', 'galau'])) {
            return self::r("Terima kasih sudah cerita. Perasaan seperti itu wajar dan kamu tidak harus menghadapinya sendirian. Coba tarik napas pelan beberapa kali, lalu ceritakan ke orang yang kamu percaya. Kalau mau, aku bisa menghubungkanmu dengan guru BK untuk ngobrol lebih lanjut.", $anon ? [$report] : [['label' => 'Ngobrol dengan guru BK', 'url' => route('siswa.karir.create', ['topik' => 'Lainnya'])]]);
        }
        if ($has(['jurusan', 'kuliah', 'karir', 'karier', 'kerja', 'beasiswa', 'universitas', 'cita-cita', 'bakat', 'minat'])) {
            return self::r($anon
                ? "Konsultasi karir tersedia untuk akun siswa terdaftar. Kamu bisa membaca artikel karir dan beasiswa di menu Info."
                : "Untuk jurusan, kuliah, kerja, atau beasiswa, kamu bisa konsultasi dengan guru BK lewat chat. Sebelum itu, coba kenali minat, kekuatan, dan nilai yang kamu pegang. Artikel di menu Info juga bisa membantu.",
                $anon ? [['label' => 'Baca Info BK', 'url' => route('siswa.informasi.index')]] : [['label' => 'Mulai konsultasi karir', 'url' => route('siswa.karir.create', ['dari' => 'beranda'])], ['label' => 'Baca Info BK', 'url' => route('siswa.informasi.index')]]);
        }
        if ($has(['skor', 'kredit', 'poin', 'pelanggaran'])) {
            return self::r($anon
                ? "Skor kredit hanya untuk akun siswa terdaftar. Skor hanya berkurang akibat pelanggaran yang dicatat dan dihitung ulang tiap tahun ajaran."
                : "Skor kredit dimulai dari 100 tiap tahun ajaran dan hanya berkurang karena pelanggaran yang dicatat. Tingkatnya: Baik (90+), Perhatian (70–89), Peringatan (50–69), Kritis (<50). Kamu bisa melihat alasan tiap catatan dan mengajukan klarifikasi ke BK.",
                $anon ? [] : [['label' => 'Lihat skor kredit', 'url' => route('siswa.kredit')]]);
        }
        if ($has(['informasi', 'artikel', 'info bk', 'pengumuman'])) {
            return self::r("Di menu Info ada artikel tentang karir, kesehatan mental, anti-perundungan, dan beasiswa dari guru BK.", [['label' => 'Buka Info BK', 'url' => route('siswa.informasi.index')]]);
        }
        if ($has(['darurat', 'bahaya', 'tolong', 'polisi', 'ambulans', 'telepon', 'nomor'])) {
            return self::r("Kalau kamu dalam bahaya sekarang, hubungi 112. SAPA 129 (KemenPPPA) melayani pengaduan kekerasan terhadap anak dan perempuan. Kamu juga bisa menghubungi guru BK sekolah.", [$help]);
        }
        if ($has(['terima kasih', 'makasih', 'thanks', 'thank you'])) {
            return self::r("Sama-sama. Kamu selalu bisa kembali kapan pun. Kalau ada yang ingin dibicarakan dengan guru BK, aku bisa bantu teruskan.", []);
        }
        if ($has(['bk', 'guru', 'konselor', 'ngobrol', 'bicara', 'curhat'])) {
            return self::r($anon
                ? "Untuk bicara dengan guru BK, kirim laporan; guru BK bisa membuka percakapan chat di laporanmu tanpa tahu siapa kamu."
                : "Kamu bisa ngobrol dengan guru BK lewat konsultasi karir (topik apa saja) atau lewat laporan bila terkait kejadian di sekolah.",
                $anon ? [$report] : [['label' => 'Ngobrol dengan guru BK', 'url' => route('siswa.karir.create', ['topik' => 'Lainnya'])], $report]);
        }

        return self::r("Aku belum paham sepenuhnya. Aku asisten otomatis sederhana, jadi jawabanku terbatas. Coba tanya soal: cara lapor, lapor anonim, cek status, konsultasi karir, atau skor kredit. Untuk hal pribadi, guru BK adalah teman bicara terbaik.", $anon ? [$report] : [['label' => 'Ngobrol dengan guru BK', 'url' => route('siswa.karir.create', ['topik' => 'Lainnya'])]]);
    }

    private static function r(string $text, array $actions): array
    {
        return ['text' => $text, 'actions' => $actions, 'risk' => false];
    }
}
