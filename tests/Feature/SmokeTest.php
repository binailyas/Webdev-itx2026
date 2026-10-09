<?php

namespace Tests\Feature;

use App\Models\AnonymousAccount;
use App\Models\ChatRoom;
use App\Models\IncidentReport;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Smoke test: setiap halaman utama tiap peran harus terbuka (200), dan aturan akses inti harus berlaku.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_public_pages(): void
    {
        foreach (['/', '/masuk', '/lapor-anonim', '/masuk-anonim', '/lupa-password', '/darurat'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guest_is_redirected_from_protected_areas(): void
    {
        foreach (['/siswa', '/bk', '/wali-kelas', '/admin'] as $url) {
            $this->get($url)->assertRedirect();
        }
    }

    public function test_admin_pages(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        foreach (['/admin', '/admin?rentang=30', '/admin/siswa', '/admin/siswa?q=Rani&status=aktif', '/admin/staf', '/admin/staf?tab=wali', '/admin/impor', '/admin/kelas', '/admin/kelas/naik-kelas',
                  '/admin/role', '/admin/kategori', '/admin/kategori?tab=skor', '/admin/audit', '/admin/pengaturan'] as $url) {
            $this->get($url)->assertOk();
        }
        $student = User::where('role_id', Role::idOf('siswa'))->first();
        $this->get("/admin/siswa/{$student->id}")->assertOk();
        $this->get('/admin/staf/' . $this->user('wk1@sekolah.sch.id')->id)->assertOk();
        $this->get('/admin/staf/' . $this->user('bk1@sekolah.sch.id')->id)->assertOk();
    }

    public function test_admin_cannot_open_reports(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $r = IncidentReport::first();
        $this->get("/bk/laporan/{$r->id}")->assertForbidden();
        $this->get("/wali-kelas/laporan/{$r->id}")->assertForbidden();
    }

    public function test_bk_pages(): void
    {
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $r = IncidentReport::whereHas('chatRoom')->first();
        $career = ChatRoom::where('type', 'karir')->first();
        foreach (['/bk', '/bk?periode=7', '/bk/laporan', '/bk/laporan?ai=tinggi&status=baru', '/bk/laporan?ai=belum', "/bk/laporan/{$r->id}", "/bk/laporan/{$r->id}/chat", "/bk/laporan/{$r->id}/chat?partial=1",
                  '/bk/karir', '/bk/karir?tab=berlangsung', "/bk/karir/{$career->id}", '/bk/ringkasan', '/bk/ringkasan/unduh', '/bk/analitik', '/bk/analitik?kata[]=kantin', '/bk/analitik/orang', '/bk/analitik/lokasi',
                  '/bk/analitik/tren', '/bk/analitik/watchlist', '/bk/pengaturan-analitik', '/bk/pengaturan-analitik?tab=stopword', '/bk/pengaturan-analitik?tab=ekstraksi',
                  '/bk/informasi', '/bk/informasi/tulis', '/bk/skor', '/bk/notifikasi', '/badges'] as $url) {
            $this->get($url)->assertOk();
        }
        $rani = $this->user('rani.putri@siswa.sekolah.sch.id');
        $this->get("/bk/skor/{$rani->id}")->assertOk();
        $this->get('/bk/analitik/orang/' . $rani->id)->assertOk();
    }

    public function test_wali_kelas_pages_and_scope(): void
    {
        $wk = $this->user('wk1@sekolah.sch.id'); // X-3, XI-1
        $this->actingAs($wk);
        $r = IncidentReport::whereHas('chatRoom')->first();
        foreach (['/wali-kelas', '/wali-kelas/laporan', '/wali-kelas/laporan?kelas=saya', "/wali-kelas/laporan/{$r->id}", '/wali-kelas/ringkasan', '/wali-kelas/analitik', '/wali-kelas/analitik/orang',
                  '/wali-kelas/analitik/lokasi', '/wali-kelas/analitik/tren', '/wali-kelas/analitik/watchlist', '/wali-kelas/skor', '/wali-kelas/notifikasi'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/wali-kelas/pengaturan-analitik')->assertRedirect('/wali-kelas/analitik/watchlist');
    }

    public function test_wali_kelas_chat_is_read_only_and_scoped(): void
    {
        $this->actingAs($this->user('wk2@sekolah.sch.id')); // X-1, X-2
        $r = IncidentReport::whereHas('chatRoom')->get()->first(fn ($x) => ! $x->involvesClassrooms($this->user('wk2@sekolah.sch.id')->classroomIds()) && ! $x->chatRoom->wk_diizinkan);
        if ($r) {
            $this->get("/wali-kelas/laporan/{$r->id}/chat")->assertForbidden();
        }
        // Tidak ada route kirim chat untuk Wali Kelas.
        $any = IncidentReport::whereHas('chatRoom')->first();
        $this->post("/wali-kelas/laporan/{$any->id}/chat", ['isi' => 'x'])->assertStatus(405);
    }

    public function test_wali_kelas_credit_rules(): void
    {
        $wk = $this->user('wk1@sekolah.sch.id');
        $this->actingAs($wk);
        $inClass = $this->user('rani.putri@siswa.sekolah.sch.id'); // X-3
        $outClass = $this->user('farhan.pratama@siswa.sekolah.sch.id'); // X-1

        $this->get("/wali-kelas/skor/{$outClass->id}")->assertForbidden();

        $cat = \App\Models\CreditCategory::first();
        // Tanpa laporan terkait ditolak.
        $this->post("/wali-kelas/skor/{$inClass->id}", ['category_id' => $cat->id, 'alasan' => 'x', 'tanggal' => now()->toDateString()])->assertSessionHasErrors('report_id');
        // Siswa di luar kelas ditolak.
        $this->post("/wali-kelas/skor/{$outClass->id}", ['category_id' => $cat->id, 'alasan' => 'x', 'tanggal' => now()->toDateString(), 'report_id' => 1])->assertForbidden();
    }

    public function test_student_flow_and_ai_is_hidden(): void
    {
        $rani = $this->user('rani.putri@siswa.sekolah.sch.id');
        $this->actingAs($rani);
        foreach (['/siswa', '/siswa/laporan', '/siswa/laporan/buat', '/siswa/karir', '/siswa/karir/baru', '/siswa/kredit', '/siswa/informasi', '/siswa/notifikasi', '/siswa/profil'] as $url) {
            $this->get($url)->assertOk();
        }

        $cat = \App\Models\IncidentCategory::first();
        $res = $this->post('/siswa/laporan', [
            'category_id' => $cat->id, 'judul' => 'Tes laporan', 'kronologi' => 'Ada yang mengejek saya di kantin setiap hari.', 'prioritas' => 'sedang',
            'tanggal_kejadian' => now()->toDateString(), 'lokasi' => 'Kantin', 'pihak_terlibat' => 'Dimas',
        ]);
        $res->assertRedirect('/siswa/laporan/terkirim');
        $this->get('/siswa/laporan/terkirim')->assertOk()->assertSee('BK-');
        $report = IncidentReport::latest('id')->first();
        $this->assertSame('baru', $report->status);
        $this->assertNotNull($report->pin_hash);

        // Saran AI tidak boleh bocor ke sisi siswa.
        $report->update(['ai_priority_suggestion' => 'tinggi', 'ai_priority_confidence' => 0.97]);
        $this->get("/siswa/laporan/{$report->ticket_code}")->assertOk()->assertDontSee('97%')->assertDontSee('Saran prioritas');
    }

    public function test_student_cannot_open_others_reports_or_staff_areas(): void
    {
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $other = IncidentReport::where('reporter_user_id', '!=', $this->user('rani.putri@siswa.sekolah.sch.id')->id)->orWhereNull('reporter_user_id')->first();
        $this->get("/siswa/laporan/{$other->ticket_code}")->assertNotFound();
        $this->get('/bk')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_anonymous_flow(): void
    {
        $this->post('/lapor-anonim')->assertRedirect('/lapor-anonim/berhasil');
        $this->get('/lapor-anonim/berhasil')->assertOk()->assertSee('Akun sementaramu siap');
        $this->get('/lapor-anonim/berhasil')->assertRedirect('/lapor-anonim'); // kredensial hanya tampil sekali

        foreach (['/siswa', '/siswa/laporan', '/siswa/laporan/buat', '/siswa/informasi', '/siswa/profil'] as $url) {
            $this->get($url)->assertOk();
        }
        // Anonim tidak punya karir & kredit.
        $this->get('/siswa/karir')->assertForbidden();
        $this->get('/siswa/kredit')->assertForbidden();

        $cat = \App\Models\IncidentCategory::first();
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'Anon', 'kronologi' => 'Laporan anonim untuk pengujian.', 'prioritas' => 'tinggi'])->assertRedirect();
        $r = IncidentReport::latest('id')->first();
        $this->assertNull($r->reporter_user_id);
        $this->assertNotNull($r->reporter_anon_id);
    }


    /** G6: satu form login untuk semua peran; peran dikenali dari akun (nama / NIS / email). */
    public function test_single_login_form_recognizes_role(): void
    {
        $this->post('/masuk', ['identifier' => 'bk1@sekolah.sch.id', 'password' => 'password'])->assertRedirect('/otp');
        $this->assertGuest();
        // Admin juga lewat /masuk (2FA), bukan form terpisah.
        $this->post('/masuk', ['identifier' => 'admin@sekolah.sch.id', 'password' => 'password'])->assertRedirect('/otp');
        $this->get('/admin/masuk')->assertRedirect('/masuk');

        // Siswa: NIS atau nama lengkap.
        $this->post('/masuk', ['identifier' => '2024001', 'password' => 'password'])->assertRedirect('/siswa');
        $this->post('/keluar');
        $this->post('/masuk', ['identifier' => 'Rani Putri', 'password' => 'password'])->assertRedirect('/siswa');
        $this->post('/keluar');
        $this->post('/masuk', ['identifier' => 'Rani Putri', 'password' => 'salah'])->assertSessionHasErrors('identifier');
    }

    /** G5: cek status kode tiket hanya di dalam akun; tidak ada di landing page. */
    public function test_ticket_status_check_only_inside_account(): void
    {
        $this->get('/cek-status')->assertNotFound();
        $this->get('/siswa/cek-status')->assertRedirect();
        $this->get('/')->assertDontSee('Cek status');

        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $r = IncidentReport::first();
        $this->get('/siswa/cek-status')->assertOk();
        $this->post('/siswa/cek-status', ['ticket' => $r->ticket_code, 'pin' => '482915'])->assertOk()->assertSee($r->ticket_code);
        $this->post('/siswa/cek-status', ['ticket' => $r->ticket_code, 'pin' => '000000'])->assertSessionHasErrors('ticket');
    }

    /** G4: hanya 3 level prioritas; akses darurat ada di landing dan beranda siswa. */
    public function test_three_priority_levels_and_emergency_access(): void
    {
        $this->assertSame(['rendah', 'sedang', 'tinggi'], IncidentReport::PRIORITIES);
        $this->get('/')->assertSee('tel:112', false);
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa')->assertSee('Butuh bantuan sekarang');
        $this->get('/siswa/laporan/buat')->assertDontSee('Darurat');
        $cat = \App\Models\IncidentCategory::first();
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'x', 'kronologi' => 'Aku ingin mati rasanya, tolong.', 'prioritas' => 'darurat'])->assertSessionHasErrors('prioritas');
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'Curhat', 'kronologi' => 'Aku ingin mati rasanya, tolong aku.', 'prioritas' => 'rendah'])->assertRedirect();
        $r = IncidentReport::latest('id')->first();
        $this->assertSame('tinggi', $r->prioritas);   // kata berisiko menaikkan ke Tinggi
        $this->assertTrue($r->risk_flagged);
    }

    /** B2 + W2 + W3: Wali Kelas meninjau dulu (hanya -> Ditinjau); BK baru melihatnya setelah itu. */
    public function test_report_flow_wali_kelas_reviews_before_bk(): void
    {
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $cat = \App\Models\IncidentCategory::first();
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'Dicoret', 'kronologi' => 'Bukuku dicoret-coret teman sekelas.', 'prioritas' => 'sedang']);
        $r = IncidentReport::latest('id')->first();
        $this->assertSame('baru', $r->status);
        $this->assertFalse($r->risk_flagged);
        auth()->logout();

        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get("/bk/laporan/{$r->id}")->assertRedirect('/bk/laporan');       // belum ditinjau WK
        $this->get('/bk/laporan?q=Dicoret')->assertOk()->assertDontSee($r->ticket_code);
        auth()->logout();

        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $this->get("/wali-kelas/laporan/{$r->id}")->assertOk();                  // melihat, tanpa auto-ubah status
        $this->assertSame('baru', $r->fresh()->status);
        $this->post("/wali-kelas/laporan/{$r->id}/status", ['status' => 'diproses', 'alasan' => 'x'])->assertSessionHasErrors('status');
        $this->post("/wali-kelas/laporan/{$r->id}/status", ['status' => 'ditinjau', 'alasan' => 'Sudah saya cek'])->assertRedirect();
        $this->assertSame('ditinjau', $r->fresh()->status);
        $this->post("/wali-kelas/laporan/{$r->id}/status", ['status' => 'baru', 'alasan' => 'balik'])->assertSessionHasErrors('status');   // tidak bisa di-undo
        auth()->logout();

        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get("/bk/laporan/{$r->id}")->assertOk();
        $this->post("/bk/laporan/{$r->id}/status", ['status' => 'diproses', 'alasan' => 'Mulai tindak lanjut'])->assertRedirect();
        $this->assertSame('diproses', $r->fresh()->status);
    }

    /** B1: lampiran dapat dibuka staf yang berhak, dan tidak oleh siswa / tanpa login. */
    public function test_attachment_can_be_opened_by_staff(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $r = IncidentReport::where('status', 'diproses')->first();
        \Illuminate\Support\Facades\Storage::disk('local')->put('laporan/test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        $a = $r->attachments()->create(['file_path' => 'laporan/test.png', 'file_name' => 'bukti.png', 'mime_type' => 'image/png', 'file_size' => 70]);

        $this->get("/bk/laporan/{$r->id}/lampiran/{$a->id}")->assertRedirect();
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'))->get("/bk/laporan/{$r->id}/lampiran/{$a->id}")->assertForbidden();
        auth()->logout();
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $res = $this->get("/bk/laporan/{$r->id}/lampiran/{$a->id}");
        $res->assertOk();
        $this->assertStringContainsString('image/png', $res->headers->get('Content-Type'));
        $this->get("/bk/laporan/{$r->id}")->assertOk()->assertSee('bukti.png');
    }

    /** B3: kata kunci dapat dibuka dan menampilkan laporan tertaut. */
    public function test_keyword_detail_lists_linked_reports(): void
    {
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $kw = \App\Models\ReportKeyword::whereHas('report', fn ($q) => $q->where('status', '!=', 'baru'))->value('keyword');
        $this->get('/bk/analitik/kata/' . rawurlencode($kw) . '?periode=0')->assertOk()->assertSee('BK-');
        $this->get('/bk/analitik?periode=0')->assertOk()->assertSee('/bk/analitik/kata/', false);
    }

    /** G3: ringkasan memuat bagian AI. T1: chatbot menjawab dan meneruskan ke BK. */
    public function test_ai_summary_and_chatbot(): void
    {
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get('/bk/ringkasan?periode=0')->assertOk()->assertSee('Ringkasan saran AI')->assertSee('Rata-rata keyakinan');
        auth()->logout();

        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa')->assertSee('Asisten RuangDengar');
        $this->postJson('/siswa/chatbot', ['message' => 'bagaimana cara lapor?'])->assertOk()->assertJsonPath('risk', false)->assertSee('Buat laporan');
        $this->postJson('/siswa/chatbot', ['message' => 'aku sedang stres'])->assertOk()->assertSee('guru BK');
        $this->postJson('/siswa/chatbot', ['message' => 'aku ingin mati'])->assertOk()->assertJsonPath('risk', true)->assertSee('112');
        $this->postJson('/siswa/chatbot', ['message' => ''])->assertStatus(422);
    }

    /** A2: tombol Back karir mengikuti jalur masuk. */
    public function test_career_back_follows_entry_path(): void
    {
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa/karir/baru?dari=beranda')->assertOk()->assertSee(route('siswa.beranda'), false);
        $res = $this->post('/siswa/karir', ['topik' => 'Beasiswa', 'pesan' => 'Apa syarat beasiswa?', 'dari' => 'beranda']);
        $room = \App\Models\ChatRoom::where('type', 'karir')->latest('id')->first();
        $res->assertRedirect(route('siswa.karir.show', ['room' => $room->id, 'dari' => 'beranda']));
        $this->get("/siswa/karir/{$room->id}?dari=beranda")->assertSee('href="' . route('siswa.beranda') . '"', false);
        $this->get("/siswa/karir/{$room->id}")->assertSee(route('siswa.karir.index', ['tab' => 'berlangsung']), false);
    }

    /** W1: chip kelas pada daftar skor wali kelas dapat di-toggle. */
    public function test_wali_kelas_class_filter_toggles(): void
    {
        $wk = $this->user('wk1@sekolah.sch.id');
        $this->actingAs($wk);
        $ids = $wk->classroomIds();
        $x3 = \App\Models\Classroom::where('nama_kelas', 'X-3')->value('id');
        $xi1 = \App\Models\Classroom::where('nama_kelas', 'XI-1')->value('id');
        $this->assertContains($x3, $ids);
        $this->get("/wali-kelas/skor?kelas[]={$x3}")->assertOk()->assertSee('Rani Putri')->assertDontSee('Bima Arya');
        $this->get("/wali-kelas/skor?kelas[]={$xi1}")->assertOk()->assertSee('Bima Arya')->assertDontSee('Rani Putri');
        $this->get('/wali-kelas/skor')->assertOk()->assertSee('Rani Putri')->assertSee('Bima Arya');
    }

    /** A1: tombol Ubah admin memakai atribut yang valid dan simpan berhasil. */
    public function test_admin_edit_buttons_render_valid_attributes(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $html = $this->get('/admin/siswa')->getContent();
        $this->assertStringContainsString('@click="openEdit(', $html);
        $this->assertStringNotContainsString("@click='openEdit", $html);
        $rani = $this->user('rani.putri@siswa.sekolah.sch.id');
        $this->put('/admin/siswa/' . $rani->id, ['name' => 'Rani P.', 'nis' => '2024001', 'classroom_id' => 3, 'is_active' => 1])->assertRedirect();
        $this->assertSame('Rani P.', $rani->fresh()->name);
    }

    public function test_branding_is_ruangdengar(): void
    {
        $this->get('/')->assertSee('RuangDengar')->assertDontSee('BK Sahabat');
        $this->get('/masuk')->assertSee('RuangDengar')->assertDontSee('BK Sahabat');
    }
}
