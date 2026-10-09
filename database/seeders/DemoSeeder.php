<?php

namespace Database\Seeders;

use App\Models\AiModelVersion;
use App\Models\AnonymousAccount;
use App\Models\Announcement;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Classroom;
use App\Models\CreditCategory;
use App\Models\CreditRecord;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\KeywordAlias;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use App\Models\WatchlistTerm;
use App\Services\KeywordExtractor;
use App\Services\Notifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data demo untuk pengembangan lokal. Semua akun berkata sandi "password".
 *  admin@sekolah.sch.id · bk1@sekolah.sch.id · wk1@sekolah.sch.id · NIS 2024001
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $ta = Classroom::currentYear();
        $hash = Hash::make('password');

        // ---- Kelas ----
        $kelas = [];
        foreach ([['X-1', 'X'], ['X-2', 'X'], ['X-3', 'X'], ['XI-1', 'XI'], ['XI-2', 'XI'], ['XII-1', 'XII']] as [$n, $t]) {
            $kelas[$n] = Classroom::create(['nama_kelas' => $n, 'tingkat' => $t, 'tahun_ajaran' => $ta]);
        }

        // ---- Staf ----
        $mk = fn (string $name, string $email, string $role) => User::create([
            'name' => $name, 'email' => $email, 'password' => $hash,
            'role_id' => Role::idOf($role), 'two_factor_enabled' => true, 'is_active' => true,
        ]);
        $admin = $mk('Admin Sekolah', 'admin@sekolah.sch.id', 'admin');
        $bk1 = $mk('Pak Budi Santoso', 'bk1@sekolah.sch.id', 'bk');
        $bk2 = $mk('Dra. Siti Aminah', 'bk2@sekolah.sch.id', 'bk');
        $wk1 = $mk('Bu Sari Wulandari', 'wk1@sekolah.sch.id', 'wali_kelas');
        $wk2 = $mk('Pak Anton Prasetyo', 'wk2@sekolah.sch.id', 'wali_kelas');
        $wk3 = $mk('Bu Rina Kartika', 'wk3@sekolah.sch.id', 'wali_kelas');
        foreach ([[$wk1, ['X-3', 'XI-1']], [$wk2, ['X-1', 'X-2']], [$wk3, ['XI-2', 'XII-1']]] as [$wk, $list]) {
            foreach ($list as $k) {
                WaliKelasAssignment::create(['user_id' => $wk->id, 'classroom_id' => $kelas[$k]->id, 'tahun_ajaran' => $ta, 'created_at' => now()]);
            }
        }

        // ---- Siswa ----
        $roster = [
            ['Rani Putri', 'X-3'], ['Dimas Pratama', 'X-3'], ['Salsa Maharani', 'X-3'], ['Rafi Ramadhan', 'X-3'],
            ['Farhan Pratama', 'X-1'], ['Ahmad Fauzi', 'X-1'], ['Dimas Setiawan', 'X-1'], ['Nadia Safitri', 'X-1'],
            ['Aisyah Rahma', 'X-2'], ['Bayu Nugroho', 'X-2'], ['Citra Nurhaliza', 'X-2'], ['Eko Saputra', 'X-2'],
            ['Bima Arya', 'XI-1'], ['Putri Lestari', 'XI-1'], ['Galih Permana', 'XI-1'], ['Hana Azzahra', 'XI-1'],
            ['Intan Permata', 'XI-2'], ['Joko Widodo Putra', 'XI-2'], ['Kirana Dewi', 'XI-2'], ['Lukman Hakim', 'XI-2'],
            ['Maya Anggraini', 'XII-1'], ['Naufal Hidayat', 'XII-1'], ['Olivia Sari', 'XII-1'], ['Prasetyo Adi', 'XII-1'],
        ];
        $students = [];
        foreach ($roster as $i => [$name, $k]) {
            $u = User::create([
                'name' => $name,
                'email' => Str::slug($name, '.') . '@siswa.sekolah.sch.id',
                'password' => $hash,
                'role_id' => Role::idOf('siswa'),
                'last_login_at' => $i % 7 === 6 ? null : now()->subDays($i % 9)->subHours($i),
                'is_active' => $i !== 6,
            ]);
            StudentProfile::create([
                'user_id' => $u->id, 'nis' => (string) (2024001 + $i), 'classroom_id' => $kelas[$k]->id,
                'angkatan' => (string) (2024 - ['X' => 0, 'XI' => 1, 'XII' => 2][$kelas[$k]->tingkat]), 'tahun_ajaran' => $ta,
            ]);
            $students[$name] = $u;
        }
        $rani = $students['Rani Putri'];

        // ---- Alias ----
        KeywordAlias::create(['alias' => 'Dimz', 'canonical' => 'dimas', 'student_user_id' => $students['Dimas Pratama']->id, 'dibuat_oleh' => $bk1->id]);

        // ---- Informasi BK ----
        foreach ([
            ['Cara memilih jurusan kuliah yang sesuai minat', 'karir', 'Mulailah dari mengenali minat, kekuatan, dan nilai yang kamu pegang. Lalu bandingkan program studi, prospek kerja, dan biaya. Jangan ragu berkonsultasi dengan guru BK.'],
            ['Mengelola stres menjelang ujian', 'kesehatan-mental', 'Atur jadwal belajar pendek, tidur cukup, dan bergerak setiap hari. Bila terasa berat, ceritakan kepada orang yang kamu percaya atau guru BK.'],
            ['Apa yang bisa kamu lakukan saat melihat perundungan', 'anti-perundungan', 'Jangan ikut menertawakan. Dekati korban dengan tenang, ajak ke tempat aman, dan laporkan lewat aplikasi ini. Laporan bisa dikirim tanpa nama.'],
            ['Beasiswa prestasi dan KIP Kuliah: panduan singkat', 'beasiswa', 'Siapkan berkas lebih awal: rapor, surat keterangan, dan esai singkat. Perhatikan tenggat pendaftaran setiap program.'],
        ] as $i => [$j, $kat, $isi]) {
            Announcement::create(['user_id' => $bk1->id, 'judul' => $j, 'slug' => Str::slug($j), 'kategori' => $kat, 'isi' => $isi, 'status' => 'terbit', 'published_at' => now()->subDays(3 + $i * 5)]);
        }
        Announcement::create(['user_id' => $bk1->id, 'judul' => 'Draf: jadwal konseling kelompok', 'slug' => 'draf-jadwal-konseling', 'kategori' => 'karir', 'isi' => 'Jadwal masih disusun.', 'status' => 'draf']);

        // ---- Model AI demo + laporan ----
        $model = AiModelVersion::updateOrCreate(['versi' => 'demo-seed'], [
            'deployed_at' => now()->subWeeks(2), 'f1_score' => 0.87, 'precision_score' => 0.88, 'recall_score' => 0.86,
            'catatan' => 'Versi contoh untuk data demo. Ganti dengan model .pkl asli di ai-service/.',
        ]);
        $cat = IncidentCategory::pluck('id', 'name');
        $anon = AnonymousAccount::create(['alias' => 'Merpati-4821', 'password_hash' => $hash, 'expires_at' => now()->addDays(30), 'last_activity_at' => now(), 'created_at' => now()->subDays(5)]);

        // [judul, kategori, kronologi, lokasi, pihak, prioritas, status, ai[label,conf,flag]|null, pelapor, hariLalu]
        $seeds = [
            ['Diejek terus soal penampilan', 'Perundungan', 'Setiap istirahat Dimas dan temannya mengejek penampilanku di kantin. Mereka menertawakan dan merekam. Sudah dua minggu dan aku jadi takut ke sekolah.', 'Kantin', 'Dimas', 'tinggi', 'baru', ['tinggi', 0.91, true], 'anon', 0],
            ['Dikunci di toilet', 'Kekerasan fisik', 'Sepulang olahraga aku didorong lalu dikunci di toilet lantai dua oleh Farhan dan Rafi. Aku baru keluar setelah satpam datang. Lenganku memar.', 'Toilet', 'Farhan, Rafi', 'darurat', 'baru', ['tinggi', 0.94, true], 'rani', 0],
            ['Komentar kasar di grup kelas', 'Perundungan online', 'Di grup kelas ada yang terus mengirim komentar kasar dan stiker menghina tentang temanku. Banyak yang ikut tertawa.', 'Media sosial', '', 'sedang', 'baru', ['sedang', 0.78, false], 'rani', 1],
            ['Barang diambil dengan paksa', 'Ancaman', 'Uang jajan diminta paksa oleh kakak kelas di belakang kantin. Katanya kalau melapor akan dicari. Kejadian sudah tiga kali.', 'Belakang kantin', 'Rafi', 'tinggi', 'ditinjau', ['tinggi', 0.83, false], 'anon', 2],
            ['Dibentak dan dikata-katai kakak kelas', 'Kekerasan verbal', 'Di koridor aku dibentak dengan kata-kata kasar karena dianggap menghalangi jalan. Salsa melihat kejadiannya.', 'Koridor', 'Salsa', 'sedang', 'diproses', ['sedang', 0.66, false], 'rani', 4],
            ['Disentuh tanpa izin saat antre', 'Pelecehan', 'Saat antre di kantin ada yang menyentuh bahuku berulang kali padahal aku sudah menolak. Aku tidak nyaman dan ingin ini berhenti.', 'Kantin', 'Bayu', 'tinggi', 'diproses', ['tinggi', 0.88, false], 'anon', 6],
            ['Teman dipukul di lapangan', 'Kekerasan fisik', 'Saat jam olahraga Dimas memukul temannya sampai jatuh. Guru olahraga sudah melerai dan korban dibawa ke UKS.', 'Lapangan', 'Dimas', 'sedang', 'selesai', ['sedang', 0.71, false], 'rani', 12],
            ['Sindiran di media sosial', 'Perundungan online', 'Ada unggahan sindiran di story yang menyinggung salah satu temanku tanpa menyebut nama.', 'Media sosial', '', 'rendah', 'selesai', ['rendah', 0.81, false], 'rani', 18],
            ['Dikucilkan di kelas', 'Perundungan', 'Beberapa teman tidak mau duduk denganku dan mengabaikan kalau aku bicara selama seminggu.', 'Kelas', '', 'rendah', 'ditolak', ['rendah', 0.62, false], 'anon', 25],
            ['Ancaman lewat pesan pribadi', 'Ancaman', 'Aku menerima pesan pribadi berisi ancaman dari akun yang tidak kukenal setelah bertengkar di kelas.', 'Media sosial', '', 'sedang', 'diarsipkan', null, 'rani', 33],
            ['Dicoret-coret bukuku', 'Perundungan', 'Buku catatanku dicoret dan sebagian halamannya disobek oleh Rafi sambil bercanda berlebihan.', 'Kelas', 'Rafi', 'rendah', 'baru', null, 'rani', 0],
            ['Teman sering menyendiri dan murung', 'Lainnya', 'Salah satu temanku sering menyendiri dan pernah bilang lelah dengan semuanya. Aku khawatir dan tidak tahu harus bagaimana.', 'Kelas', '', 'sedang', 'ditinjau', ['sedang', 0.59, false], 'anon', 3],
        ];

        $extractor = app(KeywordExtractor::class);
        foreach ($seeds as $n => [$judul, $kat, $kron, $lok, $pihak, $prio, $status, $ai, $who, $days]) {
            $created = now()->subDays($days)->subHours($n % 5 + 1);
            $r = IncidentReport::create([
                'ticket_code' => 'BK-' . str_pad((string) (200 + $n * 3), 4, '0', STR_PAD_LEFT),
                'pin_hash' => Hash::make('482915'),
                'category_id' => $cat[$kat],
                'reporter_user_id' => $who === 'rani' ? $rani->id : null,
                'reporter_anon_id' => $who === 'anon' ? $anon->id : null,
                'judul' => $judul, 'kronologi' => $kron, 'tanggal_kejadian' => $created->toDateString(),
                'lokasi' => $lok, 'pihak_terlibat' => $pihak ?: null, 'prioritas' => $prio, 'status' => $status,
                'ai_priority_suggestion' => $ai[0] ?? null, 'ai_priority_confidence' => $ai[1] ?? null,
                'ai_flagged' => $ai[2] ?? false, 'ai_model_version_id' => $ai ? $model->id : null,
                'assigned_to' => in_array($status, ['diproses', 'selesai', 'ditolak', 'diarsipkan'], true) ? $bk1->id : null,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            $r->histories()->create(['status_to' => 'baru', 'alasan' => 'Laporan dikirim.', 'created_at' => $created, 'updated_at' => $created]);
            $flow = ['ditinjau' => ['ditinjau'], 'diproses' => ['ditinjau', 'diproses'], 'selesai' => ['ditinjau', 'diproses', 'selesai'], 'ditolak' => ['ditinjau', 'ditolak'], 'diarsipkan' => ['ditinjau', 'diproses', 'selesai', 'diarsipkan']][$status] ?? [];
            $prev = 'baru';
            foreach ($flow as $i => $s) {
                $r->histories()->create([
                    'user_id' => $bk1->id, 'status_from' => $prev, 'status_to' => $s,
                    'alasan' => ['ditinjau' => 'BK sedang menelaah laporanmu.', 'diproses' => 'Tindak lanjut dimulai: pemanggilan pihak terkait.', 'selesai' => 'Penanganan tuntas dan dimediasi.', 'ditolak' => 'Tidak memenuhi syarat sebagai insiden; dialihkan ke konseling.', 'diarsipkan' => 'Disimpan.'][$s],
                    'created_at' => $created->copy()->addHours(($i + 1) * 5), 'updated_at' => $created->copy()->addHours(($i + 1) * 5),
                ]);
                $prev = $s;
            }
            if ($status !== 'baru') {
                $r->update(['opened_at' => $created->copy()->addHours(2)]);
            }
            $extractor->process($r);
        }

        // Konfirmasi sebagian pihak terlibat (profil keterlibatan).
        $peran = ['terlapor', 'korban', 'saksi'];
        \App\Models\ReportEntity::whereNotNull('kandidat_user_id')->whereHas('report', fn ($q) => $q->whereIn('status', ['diproses', 'selesai']))
            ->get()->each(fn ($e, $i) => $e->update([
                'user_id_terkait' => $e->kandidat_user_id, 'jenis_entitas' => $peran[$i % 3], 'status' => 'terkonfirmasi',
                'dikonfirmasi' => true, 'confirmed_by' => $bk1->id, 'confirmed_at' => now()->subDays(2),
            ]));

        // Chat laporan (ruang dibuka BK pada laporan yang diproses) + catatan Wali Kelas.
        foreach (IncidentReport::whereIn('status', ['diproses'])->get() as $r) {
            $room = ChatRoom::create(['type' => 'insiden', 'report_id' => $r->id, 'created_by' => $bk1->id, 'status' => 'berlangsung']);
            ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $bk1->id, 'isi' => 'Terima kasih sudah berani bercerita. Boleh ceritakan lebih detail kejadiannya?', 'is_read' => true, 'created_at' => now()->subDays(3)]);
            ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $r->reporter_user_id, 'sender_anon_id' => $r->reporter_anon_id, 'isi' => 'Kejadiannya berulang setiap istirahat. Aku merasa tidak aman.', 'is_read' => true, 'created_at' => now()->subDays(3)->addMinutes(20)]);
            ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $bk1->id, 'isi' => 'Kamu tidak sendirian. Kami akan menindaklanjuti dengan hati-hati dan identitasmu tetap aman.', 'is_read' => false, 'created_at' => now()->subDays(2)]);
            $r->notes()->create(['user_id' => $wk1->id, 'isi' => 'Dari pengamatan di kelas, siswa yang disebut memang sering memicu keributan. Siap membantu mediasi.', 'penting' => true]);
        }

        // Sesi karir
        $room = ChatRoom::create(['type' => 'karir', 'career_user_id' => $rani->id, 'topik' => 'Jurusan kuliah', 'status' => 'menunggu', 'created_by' => $rani->id]);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $rani->id, 'isi' => 'Saya tertarik desain tapi orang tua ingin saya masuk kedokteran. Bagaimana memilih?']);
        $room2 = ChatRoom::create(['type' => 'karir', 'career_user_id' => $students['Bima Arya']->id, 'topik' => 'Beasiswa', 'status' => 'berlangsung', 'created_by' => $students['Bima Arya']->id]);
        ChatMessage::create(['chat_room_id' => $room2->id, 'sender_user_id' => $students['Bima Arya']->id, 'isi' => 'Apa syarat KIP Kuliah?', 'created_at' => now()->subDay()]);
        ChatMessage::create(['chat_room_id' => $room2->id, 'sender_user_id' => $bk1->id, 'isi' => 'Siapkan rapor dan surat keterangan penghasilan orang tua ya.', 'created_at' => now()->subHours(20)]);
        $room3 = ChatRoom::create(['type' => 'karir', 'career_user_id' => $students['Aisyah Rahma']->id, 'topik' => 'Minat dan bakat', 'status' => 'menunggu', 'created_by' => $students['Aisyah Rahma']->id, 'created_at' => now()->subDays(2)]);
        ChatMessage::create(['chat_room_id' => $room3->id, 'sender_user_id' => $students['Aisyah Rahma']->id, 'isi' => 'Bagaimana cara tahu bakat saya?']);

        // Skor kredit
        $ck = CreditCategory::pluck('id', 'name');
        foreach ([['Rani Putri', 'Terlambat', 5, 'Terlambat tiga kali dalam seminggu.', 20, null], ['Rani Putri', 'Atribut tidak lengkap', 5, 'Tidak memakai dasi saat upacara.', 9, null],
                  ['Dimas Pratama', 'Perkelahian', 30, 'Memukul teman saat olahraga.', 12, 'BK-0236'], ['Dimas Pratama', 'Membolos', 15, 'Tidak masuk tanpa keterangan.', 30, null],
                  ['Rafi Ramadhan', 'Terlambat', 5, 'Terlambat.', 6, null]] as [$nm, $kt, $p, $al, $d, $tk]) {
            CreditRecord::create([
                'student_id' => $students[$nm]->id, 'user_id_pencatat' => $nm === 'Rani Putri' ? $wk1->id : $bk1->id, 'category_id' => $ck[$kt],
                'poin_dikurangi' => $p, 'alasan' => $al, 'tanggal' => now()->subDays($d), 'tahun_ajaran' => $ta,
                'report_id' => $tk ? IncidentReport::where('ticket_code', $tk)->value('id') : null,
            ]);
        }
        $voided = CreditRecord::where('alasan', 'Terlambat.')->first();
        $voided?->update(['voided_at' => now()->subDays(3), 'voided_by' => $bk1->id, 'void_reason' => 'Salah input, siswa ternyata izin sakit.']);

        // Watchlist & notifikasi contoh
        WatchlistTerm::create(['user_id' => $bk1->id, 'term' => 'kantin', 'ambang' => 5, 'catatan' => 'Titik rawan']);
        WatchlistTerm::create(['user_id' => $bk1->id, 'term' => 'dimas', 'ambang' => 3]);
        WatchlistTerm::create(['user_id' => $wk1->id, 'term' => 'toilet', 'ambang' => 2]);

        $first = IncidentReport::where('ai_flagged', true)->first();
        Notifier::toRole('bk', 'ai_flagged', ['report_id' => $first?->id, 'pesan' => 'Laporan ditandai berisiko tinggi oleh analisis otomatis.']);
        Notifier::to($wk1, 'kelas_saya', ['report_id' => $first?->id, 'pesan' => 'Siswa kelas asuhanmu mungkin terlibat dalam laporan baru.']);
        Notifier::to($rani, 'status', ['pesan' => 'Status laporanmu berubah menjadi Diproses.']);
        Notifier::to($rani, 'skor', ['pesan' => 'Ada catatan pengurangan skor baru.']);
        Notifier::to($rani, 'info', ['pesan' => 'Informasi baru dari BK: Mengelola stres menjelang ujian.']);

        // Riwayat login 90 hari (untuk grafik "Pengguna aktif harian") + beberapa aktivitas admin.
        $ids = array_map(fn ($u) => $u->id, array_values($students));
        $ids = array_merge($ids, [$bk1->id, $bk2->id, $wk1->id, $wk2->id, $wk3->id, $admin->id]);
        mt_srand(42);
        $rows = [];
        for ($d = 89; $d >= 0; $d--) {
            $day = now()->subDays($d);
            $n = $day->isWeekend() ? mt_rand(2, 6) : mt_rand(12, 26);
            foreach ((array) array_rand(array_flip($ids), min($n, count($ids))) as $uid) {
                $rows[] = ['user_id' => $uid, 'action' => 'login.sukses', 'created_at' => $day->copy()->setTime(mt_rand(6, 20), mt_rand(0, 59))];
            }
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            \App\Models\AuditLog::insert($chunk);
        }
        foreach ([['akun.dibuat', 'Budi Hartono', 2], ['akun.reset_sandi', 'Rani Putri', 1], ['role.diubah', 'Bu Sari Wulandari', 4], ['kelas.ditetapkan', 'Bu Sari Wulandari', 4], ['akun.dinonaktifkan', 'Dimas Setiawan', 6], ['login.gagal', 'x', 0]] as [$a, $t, $ago]) {
            \App\Models\AuditLog::create(['user_id' => $a === 'login.gagal' ? null : $admin->id, 'action' => $a, 'data' => ['target' => $t], 'created_at' => now()->subDays($ago)->subMinutes(mt_rand(5, 300))]);
        }
    }
}
