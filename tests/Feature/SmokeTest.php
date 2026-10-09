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
        foreach (['/', '/masuk', '/admin/masuk', '/lapor-anonim', '/masuk-anonim', '/lupa-password', '/darurat', '/cek-status'] as $url) {
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

    public function test_login_requires_2fa_for_staff(): void
    {
        $res = $this->post('/masuk', ['identifier' => 'bk1@sekolah.sch.id', 'password' => 'password']);
        $res->assertRedirect('/otp');
        $this->assertGuest();

        $this->post('/masuk', ['identifier' => '2024001', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_ticket_pin_status_check(): void
    {
        $r = IncidentReport::first();
        $this->post('/cek-status', ['ticket' => $r->ticket_code, 'pin' => '482915'])->assertOk()->assertSee($r->ticket_code);
        $this->post('/cek-status', ['ticket' => $r->ticket_code, 'pin' => '000000'])->assertSessionHasErrors('ticket');
    }
}
