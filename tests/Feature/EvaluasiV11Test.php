<?php

namespace Tests\Feature;

use App\Models\AnonymousAccount;
use App\Models\Announcement;
use App\Models\ChatRoom;
use App\Models\Classroom;
use App\Models\CreditCategory;
use App\Models\CreditRecord;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\User;
use App\Support\Perm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Evaluasi RuangDengar v1.1: logo, izin dinamis, skor, siklus kasus, form laporan, informasi, dll. */
class EvaluasiV11Test extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Perm::flush();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function newReport(array $o = []): IncidentReport
    {
        return IncidentReport::create($o + [
            'ticket_code' => IncidentReport::newTicketCode(), 'category_id' => IncidentCategory::first()->id,
            'judul' => 'Uji', 'kronologi' => 'Teks uji laporan.', 'prioritas' => 'rendah', 'status' => 'baru',
        ]);
    }

    public function test_logo_icons_manifest_and_landing(): void
    {
        foreach (['images/logo-ruangdengar.svg', 'images/logo-ruangdengar-ungu.svg', 'images/icon.svg', 'favicon.ico', 'favicon.svg', 'favicon-16x16.png', 'favicon-32x32.png',
                  'apple-touch-icon.png', 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'site.webmanifest', 'images/hero.svg', 'images/login.svg'] as $f) {
            $this->assertFileExists(public_path($f), $f);
        }
        $this->assertSame('RuangDengar', json_decode(file_get_contents(public_path('site.webmanifest')), true)['name']);

        $this->get('/')->assertOk()->assertSee('images/logo-ruangdengar.svg', false)->assertSee('favicon.ico', false)->assertSee('apple-touch-icon.png', false)
            ->assertSee('Ceritakan saja')->assertSee('tel:112', false)->assertDontSee('Cek status')->assertDontSee('BK Sahabat');
        $this->get('/masuk')->assertOk()->assertSee('images/logo-ruangdengar.svg', false)->assertSee('images/login.svg', false);
    }

    public function test_database_is_named_ruang_dengar(): void
    {
        $this->assertStringContainsString('DB_DATABASE=ruang_dengar', file_get_contents(base_path('.env.example')));
        $this->assertStringNotContainsString('bk_sahabat', file_get_contents(base_path('.env.example')));
    }

    public function test_role_matrix_is_editable_and_enforced(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $this->get('/admin/role')->assertOk()->assertSee('Simpan perubahan');

        // Semua sel yang dapat diedit dicentang, kecuali BK -> baca laporan.
        $payload = [];
        foreach (config('permissions.groups') as $features) {
            foreach (array_keys($features) as $feature) {
                foreach (config('permissions.editable_roles') as $role) {
                    if (Perm::editable($role, $feature) && ! ($role === 'bk' && $feature === 'laporan.baca')) {
                        $payload['p'][$feature][$role] = '1';
                    }
                }
            }
        }
        $payload['p']['akun.kelola']['bk'] = '1';   // sel terkunci: harus diabaikan
        $this->post('/admin/role', $payload)->assertRedirect();
        Perm::flush();

        $this->assertSame('n', Perm::value('bk', 'laporan.baca'));
        $this->assertSame('n', Perm::value('bk', 'akun.kelola'));
        $this->assertSame('y', Perm::value('bk', 'laporan.status'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.diubah']);
        auth()->logout();

        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get('/bk/laporan')->assertForbidden();
        $this->get('/bk')->assertOk()->assertDontSee('<span class="flex-1">Laporan</span>', false)->assertSee('<span class="flex-1">Arsip kasus</span>', false);   // menu Laporan disembunyikan
        $this->get('/bk/ringkasan')->assertOk();                                  // izin lain tidak terganggu
    }

    public function test_wali_kelas_analytics_settings_permission_can_be_granted(): void
    {
        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $this->get('/wali-kelas')->assertOk()->assertDontSee('Pengaturan analitik');
        $this->get('/wali-kelas/pengaturan-analitik')->assertForbidden();
        auth()->logout();

        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $this->assertTrue(Perm::editable('wali_kelas', 'analitik.pengaturan'));
        $payload = ['p' => ['analitik.pengaturan' => ['wali_kelas' => '1']]];
        // Kirim seluruh sel lain tetap tercentang agar tidak tercabut.
        foreach (config('permissions.groups') as $features) {
            foreach (array_keys($features) as $feature) {
                foreach (config('permissions.editable_roles') as $role) {
                    if (Perm::editable($role, $feature) && Perm::value($role, $feature) !== 'n') {
                        $payload['p'][$feature][$role] = '1';
                    }
                }
            }
        }
        $this->post('/admin/role', $payload)->assertRedirect();
        Perm::flush();
        auth()->logout();

        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $this->get('/wali-kelas')->assertSee('Pengaturan analitik');
        $this->get('/wali-kelas/pengaturan-analitik')->assertRedirect('/wali-kelas/analitik/watchlist');
    }

    public function test_score_thresholds_are_admin_editable_and_shown_to_student(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $base = ['nama_sekolah' => 'SMA Uji', 'anon_days' => 30, 'retensi' => 'tahun_ajaran', 'ket_baik' => 'Sangat baik sekali', 'ket_do' => 'Batas pemberhentian uji'];
        $this->post('/admin/pengaturan', $base + ['skor_baik' => 60, 'skor_perhatian' => 80, 'skor_peringatan' => 50, 'skor_do' => 10])->assertSessionHasErrors('skor_perhatian');
        $this->post('/admin/pengaturan', $base + ['skor_baik' => 95, 'skor_perhatian' => 80, 'skor_peringatan' => 60, 'skor_do' => 20])->assertSessionHasNoErrors();
        auth()->logout();

        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa/kredit')->assertOk()->assertSee('95–100')->assertSee('80–94')->assertSee('Sangat baik sekali')->assertSee('≤ 20')->assertSee('Batas pemberhentian uji');
    }

    public function test_score_is_not_reset_when_promoting_classes(): void
    {
        $rani = $this->user('rani.putri@siswa.sekolah.sch.id');
        $before = $rani->creditScore();
        $this->assertLessThan(100, $before);

        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $map = Classroom::where('tahun_ajaran', setting('tahun_ajaran'))->get()->mapWithKeys(fn ($c) => [$c->id => preg_replace('/^XI-/', 'XII-', preg_replace('/^X-/', 'XI-', $c->nama_kelas))]);
        $this->post('/admin/kelas/naik-kelas', ['tahun_baru' => '2030/2031', 'map' => $map->all(), 'konfirmasi' => '1'])->assertRedirect()->assertSessionHas('status');

        $rani = $rani->fresh();
        $this->assertSame($before, $rani->creditScore());        // saldo terbawa
        $this->assertSame($before, $rani->carriedScore());       // dicatat sebagai saldo bawaan
        $this->assertDatabaseHas('audit_logs', ['action' => 'naik_kelas']);
    }

    public function test_involved_people_roles_and_self_option(): void
    {
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa/laporan/buat')->assertOk()->assertSee('Saya sendiri')->assertSee('Terduga pelaku')->assertDontSee('Seberapa mendesak');
        $cat = IncidentCategory::first();
        $this->post('/siswa/laporan', [
            'category_id' => $cat->id, 'judul' => 'Peran', 'kronologi' => 'Dimas mengejek saya dan Salsa melihatnya di kantin.',
            'pihak' => [['nama' => 'Dimas', 'peran' => 'terlapor', 'self' => 0], ['nama' => 'Salsa', 'peran' => 'saksi', 'self' => 0], ['nama' => 'Saya sendiri', 'peran' => 'korban', 'self' => 1]],
        ])->assertRedirect('/siswa/laporan/terkirim');

        $r = IncidentReport::latest('id')->first();
        $this->assertSame('Dimas, Salsa', $r->pihak_terlibat);
        $roles = $r->entities->pluck('jenis_entitas', 'nama_entitas');
        $this->assertSame('terlapor', $roles['Dimas']);
        $this->assertSame('saksi', $roles['Salsa']);
        $this->assertSame('korban', $roles['Pelapor (saya sendiri)']);
        $this->assertSame(0, $r->entities->whereNotNull('user_id_terkait')->count());   // belum terkonfirmasi

        // B3: BK/WK dapat mengubah peran, tercatat di audit log.
        $e = $r->entities->firstWhere('nama_entitas', 'Dimas');
        auth()->logout();
        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $this->post("/wali-kelas/laporan/{$r->id}/pihak/{$e->id}", ['peran' => 'terlapor', 'student_id' => $e->kandidat_user_id])->assertRedirect();
        $this->post("/wali-kelas/laporan/{$r->id}/pihak/{$e->id}", ['peran' => 'saksi', 'student_id' => $e->kandidat_user_id])->assertRedirect();
        $this->assertSame('saksi', $e->fresh()->jenis_entitas);
        $this->assertDatabaseHas('audit_logs', ['action' => 'laporan.pihak_diubah']);
    }

    public function test_student_can_chat_with_bk_right_after_reporting_even_anonymous(): void
    {
        $this->post('/lapor-anonim')->assertRedirect();
        $cat = IncidentCategory::first();
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'Anon', 'kronologi' => 'Bukuku dicoret-coret teman.'])->assertRedirect();
        $r = IncidentReport::latest('id')->first();
        $this->get('/siswa/laporan/terkirim')->assertOk()->assertSee('Chat dengan BK sekarang');
        // sent halaman sekali pakai; lanjut dari halaman detail
        $this->get("/siswa/laporan/{$r->ticket_code}")->assertOk()->assertSee('Chat dengan BK');
        $this->post("/siswa/laporan/{$r->ticket_code}/chat/mulai")->assertRedirect("/siswa/laporan/{$r->ticket_code}/chat");
        $this->assertNotNull($r->fresh()->chatRoom);
        $this->assertNull($r->fresh()->chatRoom->created_by);   // anonim: tanpa identitas
        $this->post("/siswa/laporan/{$r->ticket_code}/chat", ['isi' => 'Halo, saya butuh bantuan.'])->assertRedirect();
        $this->assertDatabaseHas('chat_messages', ['isi' => 'Halo, saya butuh bantuan.']);
        auth('anon')->logout();

        // Walau belum ditinjau Wali Kelas, laporan dengan chat siswa terlihat oleh BK (tidak tertahan).
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get("/bk/laporan/{$r->id}")->assertOk();
        $this->get("/bk/laporan/{$r->id}/chat")->assertOk()->assertSee('Halo, saya butuh bantuan.');
    }

    public function test_quick_exit_logs_out_and_goes_to_landing(): void
    {
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa/laporan/buat')->assertOk()->assertSee('/keluar-cepat', false);
        $this->post('/keluar-cepat')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_ai_result_becomes_initial_priority_and_flags(): void
    {
        config(['ai.enabled' => true, 'ai.endpoint' => 'http://ai.test']);
        Http::fake(['ai.test/classify' => Http::response(['label' => 'tinggi', 'confidence' => 0.93, 'model_version' => 'uji-1', 'meta' => ['f1_score' => 0.9]])]);

        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $cat = IncidentCategory::first();
        $this->post('/siswa/laporan', ['category_id' => $cat->id, 'judul' => 'Uji AI', 'kronologi' => 'Ada yang mengejek penampilanku setiap hari.'])->assertRedirect();
        $r = IncidentReport::latest('id')->first();
        $this->assertSame('tinggi', $r->ai_priority_suggestion);
        $this->assertSame('tinggi', $r->prioritas);   // saran AI menjadi prioritas awal
        $this->assertTrue($r->ai_flagged);
        $this->assertSame('uji-1', $r->aiModel->versi);
    }

    public function test_case_lifecycle_reopen_notify_and_auto_archive(): void
    {
        $anon = AnonymousAccount::create(['alias' => 'Elang-9999', 'password_hash' => 'x', 'expires_at' => now()->addDays(30), 'created_at' => now()]);
        $r = $this->newReport(['reporter_anon_id' => $anon->id, 'status' => 'selesai', 'selesai_at' => now()->subDays(26)]);

        $this->artisan('kasus:arsipkan')->assertSuccessful();
        $this->assertNotNull($r->fresh()->arsip_notified_at);              // hari ke-25: pemberitahuan
        $this->assertSame('selesai', $r->fresh()->status);
        $this->assertDatabaseHas('notifications', ['anon_id' => $anon->id, 'type' => 'status']);
        $this->assertDatabaseHas('anonymous_accounts', ['id' => $anon->id]);   // belum ditutup

        // Dibuka kembali oleh BK: hitungan diatur ulang.
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->post("/bk/laporan/{$r->id}/status", ['status' => 'diproses', 'alasan' => 'Ada informasi baru'])->assertRedirect();
        $this->assertNull($r->fresh()->selesai_at);
        $this->post("/bk/laporan/{$r->id}/status", ['status' => 'selesai', 'alasan' => 'Tuntas'])->assertRedirect();
        $this->assertNotNull($r->fresh()->selesai_at);
        $this->assertNull($r->fresh()->arsip_notified_at);

        // 31 hari kemudian: diarsipkan otomatis, akses akun anonim ditutup, isi kasus tetap ada.
        $r->update(['selesai_at' => now()->subDays(31)]);
        $this->artisan('kasus:arsipkan')->assertSuccessful();
        $r->refresh();
        $this->assertSame('diarsipkan', $r->status);
        $this->assertNotNull($r->archived_at);
        $this->assertDatabaseMissing('anonymous_accounts', ['id' => $anon->id]);
        $this->assertSame('Teks uji laporan.', $r->kronologi);
        $this->assertNull($r->reporter_anon_id);
    }

    public function test_anonymous_account_kept_while_other_cases_open(): void
    {
        $anon = AnonymousAccount::create(['alias' => 'Kenari-1234', 'password_hash' => 'x', 'expires_at' => now()->addDays(30), 'created_at' => now()]);
        $done = $this->newReport(['reporter_anon_id' => $anon->id, 'status' => 'selesai', 'selesai_at' => now()->subDays(40)]);
        $this->newReport(['reporter_anon_id' => $anon->id, 'status' => 'diproses']);
        $this->artisan('kasus:arsipkan')->assertSuccessful();
        $this->assertSame('diarsipkan', $done->fresh()->status);
        $this->assertDatabaseHas('anonymous_accounts', ['id' => $anon->id]);   // masih punya kasus aktif
    }

    public function test_archive_page_for_bk_and_wali_kelas(): void
    {
        $arsip = IncidentReport::where('status', 'diarsipkan')->first();
        $this->assertNotNull($arsip);
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get('/bk/arsip')->assertOk()->assertSee('Arsip kasus')->assertSee($arsip->ticket_code);
        $this->get('/bk/arsip?q=tidak-ada-xyz')->assertOk()->assertDontSee($arsip->ticket_code);
        auth()->logout();
        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $this->get('/wali-kelas/arsip')->assertOk()->assertSee($arsip->ticket_code);
    }

    public function test_info_image_upload_validation_and_serving(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get('/bk/informasi/tulis')->assertOk()->assertSee('enctype="multipart/form-data"', false);

        $base = ['judul' => 'Tips belajar', 'kategori' => 'karir', 'isi' => 'Isi artikel.', 'aksi' => 'terbit'];
        $this->post('/bk/informasi', $base + ['gambar' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('gambar');
        $this->post('/bk/informasi', $base + ['gambar' => UploadedFile::fake()->create('besar.png', 4096, 'image/png')])->assertSessionHasErrors('gambar');
        $this->post('/bk/informasi', $base + ['gambar' => UploadedFile::fake()->createWithContent('ok.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='))])->assertSessionHasNoErrors()->assertRedirect('/bk/informasi');

        $a = Announcement::where('judul', 'Tips belajar')->firstOrFail();
        $this->assertNotNull($a->image_path);
        Storage::disk('public')->assertExists($a->image_path);

        auth()->logout();
        $this->actingAs($this->user('rani.putri@siswa.sekolah.sch.id'));
        $this->get('/siswa/informasi')->assertOk()->assertSee($a->image_url, false);
        $this->get('/siswa/informasi/' . $a->slug)->assertOk()->assertSee($a->image_url, false);
        $res = $this->get($a->image_url);
        $res->assertOk();
        $this->assertStringContainsString('image/png', $res->headers->get('Content-Type'));
        $this->get('/media/informasi/tidak-terdaftar.png')->assertNotFound();
    }

    public function test_bk_dashboard_cards_and_staff_ui_changes(): void
    {
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get('/bk')->assertOk()->assertSee('Total laporan selesai')->assertSee('Laporan diproses')->assertSee('Sesi karir menunggu')->assertDontSee('Rata-rata respons')
            ->assertSee('aria-label="Breadcrumb"', false)->assertSee('Arsip kasus');
        $this->get('/bk/laporan')->assertOk()->assertSee('Dibuat')->assertDontSee('>Umur<', false);
        $this->get('/bk/laporan')->assertSee('href="' . route('bk.dashboard') . '"', false);   // breadcrumb dapat diklik
        auth()->logout();
        $this->get('/masuk')->assertSee('function confirmModal', false);   // dialog konfirmasi bertema
    }

    public function test_auto_summary_card_on_report_detail(): void
    {
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $r = IncidentReport::where('status', 'diproses')->first();
        $this->get("/bk/laporan/{$r->id}")->assertOk()->assertSee('Ringkasan otomatis')->assertSee('bukan keputusan');
    }

    public function test_wali_kelas_status_button_has_themed_confirmation(): void
    {
        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $r = IncidentReport::where('status', 'baru')->first();
        $html = $this->get("/wali-kelas/laporan/{$r->id}")->assertOk()->getContent();
        $this->assertStringContainsString('data-confirm=', $html);
        $this->assertStringNotContainsString('onclick="return confirm', $html);
        $this->assertStringNotContainsString('<select id="status"', $html);   // W3: komponen statis, bukan dropdown
    }
}
