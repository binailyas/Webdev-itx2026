<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Evaluasi RuangDengar v1.2 (rilis v1.3): UI siswa, salin, prioritas, badge chat, sandi, kategori, wali kelas, matriks peran. */
class EvaluasiV12Test extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function nisUser(string $nama): User
    {
        return User::where('name', $nama)->firstOrFail();
    }

    private function newReport(array $o = []): IncidentReport
    {
        return IncidentReport::create($o + [
            'ticket_code' => IncidentReport::newTicketCode(), 'category_id' => IncidentCategory::first()->id,
            'judul' => 'Uji', 'kronologi' => 'Teks uji laporan.', 'prioritas' => 'rendah', 'status' => 'baru',
        ]);
    }

    private function mention(IncidentReport $r, User $student): void
    {
        $r->entities()->create(['nama_entitas' => $student->name, 'jenis_entitas' => 'terlapor', 'status' => 'saran', 'kandidat_user_id' => $student->id]);
    }

    public function test_report_form_has_priority_cards_and_edit_overlay_and_copy_buttons(): void
    {
        $this->actingAs($this->nisUser('Rani Putri'));
        $this->get('/siswa/laporan/buat')->assertOk()->assertSee('Seberapa mendesak')->assertSee('edit-overlay', false)->assertSee('Simpan perubahan');

        $this->post('/siswa/laporan', ['category_id' => IncidentCategory::first()->id, 'judul' => 'Salin', 'kronologi' => 'Ada yang mengejek saya setiap hari.', 'prioritas' => 'tinggi'])->assertRedirect();
        $r = IncidentReport::latest('id')->first();
        $this->get('/siswa/laporan/terkirim')->assertOk()->assertSee('Salin kode tiket')->assertSee('Salin PIN');
        $this->get("/siswa/laporan/{$r->ticket_code}")->assertOk()->assertSee('Salin kode tiket');
    }

    public function test_ai_only_suggests_and_staff_sees_separate_values(): void
    {
        $r = $this->newReport(['prioritas' => 'sedang', 'prioritas_siswa' => 'sedang', 'ai_priority_suggestion' => 'tinggi', 'ai_priority_confidence' => 0.8, 'status' => 'ditinjau']);
        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get("/bk/laporan/{$r->id}")->assertOk()->assertSee('Pilihan siswa')->assertSee('Prioritas akhir');
        $this->assertSame('sedang', $r->fresh()->prioritas);
    }

    public function test_chat_badge_counts_only_other_party_unread(): void
    {
        $siswa = $this->nisUser('Rani Putri');
        $bk = $this->user('bk1@sekolah.sch.id');
        $r = $this->newReport(['reporter_user_id' => $siswa->id, 'status' => 'ditinjau']);
        $room = ChatRoom::create(['type' => 'insiden', 'report_id' => $r->id, 'created_by' => $bk->id, 'status' => 'berlangsung']);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $siswa->id, 'isi' => 'Pesan saya sendiri 1']);
        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $siswa->id, 'isi' => 'Pesan saya sendiri 2']);

        $this->assertSame(0, $room->messages()->unreadForStudent()->count());   // pesan sendiri tidak dihitung
        $this->assertSame(2, $room->messages()->unreadForStaff()->count());

        ChatMessage::create(['chat_room_id' => $room->id, 'sender_user_id' => $bk->id, 'isi' => 'Balasan BK']);
        $this->assertSame(1, $room->messages()->unreadForStudent()->count());

        $this->actingAs($siswa);
        $this->get('/siswa/laporan')->assertOk()->assertSee('1 pesan baru')->assertDontSee('3 pesan baru');
        $this->get("/siswa/laporan/{$r->ticket_code}/chat")->assertOk();
        $this->assertSame(0, $room->messages()->unreadForStudent()->count());   // dibuka -> terbaca
    }

    public function test_password_change_for_student_and_staff(): void
    {
        $siswa = $this->nisUser('Rani Putri');
        $this->actingAs($siswa);
        $this->post('/siswa/profil/kata-sandi', ['current' => 'salah', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])->assertSessionHasErrors('current');
        $this->post('/siswa/profil/kata-sandi', ['current' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
        $this->post('/siswa/profil/kata-sandi', ['current' => 'password', 'password' => 'sandibaru123', 'password_confirmation' => 'sandibaru123'])->assertSessionHas('status');
        $this->assertTrue(Hash::check('sandibaru123', $siswa->fresh()->password));
        auth()->logout();

        foreach (['admin@sekolah.sch.id', 'bk1@sekolah.sch.id', 'wk1@sekolah.sch.id'] as $email) {
            $u = $this->user($email);
            $this->actingAs($u);
            $this->get('/akun/kata-sandi')->assertOk()->assertSee('Ubah kata sandi');
            $this->post('/akun/kata-sandi', ['current' => 'password', 'password' => 'rahasia-baru-1', 'password_confirmation' => 'rahasia-baru-1'])->assertSessionHas('status');
            $this->assertTrue(Hash::check('rahasia-baru-1', $u->fresh()->password), $email);
            auth()->logout();
        }
    }

    public function test_incident_category_order_stays_unique_and_sequential(): void
    {
        $this->actingAs($this->user('admin@sekolah.sch.id'));
        $cats = IncidentCategory::orderBy('urutan')->get();
        $last = $cats->last();
        $this->put("/admin/kategori/insiden/{$last->id}", ['name' => $last->name, 'urutan' => 1, 'is_active' => 1])->assertRedirect();
        $order = IncidentCategory::orderBy('urutan')->pluck('urutan')->all();
        $this->assertSame(range(1, count($order)), $order);
        $this->assertSame($last->id, IncidentCategory::orderBy('urutan')->first()->id);

        $this->post('/admin/kategori', ['type' => 'insiden', 'name' => 'Baru uji', 'urutan' => 2])->assertRedirect();
        $order = IncidentCategory::orderBy('urutan')->pluck('urutan')->all();
        $this->assertSame(range(1, count($order)), $order);
        $this->assertSame('Baru uji', IncidentCategory::where('urutan', 2)->value('name'));

        $this->delete('/admin/kategori/insiden/' . IncidentCategory::where('name', 'Baru uji')->value('id'))->assertRedirect();
        $order = IncidentCategory::orderBy('urutan')->pluck('urutan')->all();
        $this->assertSame(range(1, count($order)), $order);
    }

    public function test_career_form_has_themed_confirmation(): void
    {
        $this->actingAs($this->nisUser('Rani Putri'));
        $this->get('/siswa/karir/baru')->assertOk()->assertSee('data-confirm=', false);
    }

    public function test_wali_kelas_is_limited_to_own_classes_everywhere(): void
    {
        $wk1 = $this->user('wk1@sekolah.sch.id');      // X-3, XI-1
        $other = $this->nisUser('Farhan Pratama');     // X-1 (wk2)
        $mine = $this->nisUser('Dimas Pratama');       // X-3 (wk1)

        $rOther = $this->newReport(['judul' => 'Laporan kelas lain']);
        $this->mention($rOther, $other);
        $rMine = $this->newReport(['judul' => 'Laporan kelas saya']);
        $this->mention($rMine, $mine);
        $rOpen = $this->newReport(['judul' => 'Laporan belum diklasifikasi']);

        $this->actingAs($wk1);
        $this->get("/wali-kelas/laporan/{$rOther->id}")->assertForbidden();
        $this->post("/wali-kelas/laporan/{$rOther->id}/status", ['status' => 'ditinjau', 'alasan' => 'uji'])->assertForbidden();
        $this->post("/wali-kelas/laporan/{$rOther->id}/prioritas", ['priority_set' => 'tinggi'])->assertForbidden();
        $this->get("/wali-kelas/laporan/{$rMine->id}")->assertOk();
        $this->get("/wali-kelas/laporan/{$rOpen->id}")->assertForbidden();     // V13-2: tanpa kelas = bukan untuk Wali Kelas
        $this->get('/wali-kelas/laporan')->assertOk()->assertSee('Laporan kelas saya')->assertDontSee('Laporan belum diklasifikasi')->assertDontSee('Laporan kelas lain');
        $this->get('/wali-kelas')->assertOk()->assertDontSee('Laporan kelas lain')->assertDontSee('Laporan belum diklasifikasi');

        // Fitur "semua kelas" sudah tidak ada.
        $this->get('/wali-kelas/ringkasan')->assertOk()->assertDontSee('Semua kelas');
        $this->get('/wali-kelas')->assertOk()->assertDontSee('Lihat semua kelas');
        $this->post('/wali-kelas/analitik/semua-kelas')->assertStatus(404);
        auth()->logout();

        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->get("/bk/laporan/{$rOpen->id}")->assertOk();                       // laporan tanpa kelas ditangani BK
        auth()->logout();

        $this->actingAs($this->user('wk2@sekolah.sch.id'));
        $this->get("/wali-kelas/laporan/{$rOther->id}")->assertOk();
        $this->get("/wali-kelas/laporan/{$rMine->id}")->assertForbidden();
    }

    public function test_wali_kelas_may_change_priority_only_while_new(): void
    {
        $r = $this->newReport();
        $this->mention($r, $this->nisUser('Dimas Pratama'));   // kelas X-3 (wk1)
        $wk = $this->user('wk1@sekolah.sch.id');

        $this->actingAs($wk);
        $this->post("/wali-kelas/laporan/{$r->id}/prioritas", ['priority_set' => 'sedang'])->assertRedirect();
        $this->assertSame('sedang', $r->fresh()->prioritas);
        $this->assertDatabaseHas('audit_logs', ['action' => 'laporan.prioritas']);

        $r->update(['status' => 'ditinjau']);
        $this->get("/wali-kelas/laporan/{$r->id}")->assertOk()->assertSee('hanya bisa diubah Wali Kelas saat status Baru');
        $this->post("/wali-kelas/laporan/{$r->id}/prioritas", ['priority_set' => 'tinggi'])->assertForbidden();
        $this->assertSame('sedang', $r->fresh()->prioritas);
        auth()->logout();

        $this->actingAs($this->user('bk1@sekolah.sch.id'));
        $this->post("/bk/laporan/{$r->id}/prioritas", ['priority_set' => 'tinggi'])->assertRedirect();   // BK tidak dibatasi
        $this->assertSame('tinggi', $r->fresh()->prioritas);
    }

    public function test_wali_kelas_credit_record_does_not_require_report_link(): void
    {
        $this->actingAs($this->user('wk1@sekolah.sch.id'));
        $student = $this->nisUser('Dimas Pratama');
        $cat = \App\Models\CreditCategory::first();
        $this->post("/wali-kelas/skor/{$student->id}", ['category_id' => $cat->id, 'alasan' => 'Terlambat berulang', 'tanggal' => now()->toDateString()])->assertRedirect();
        $this->assertDatabaseHas('credit_records', ['student_id' => $student->id, 'report_id' => null]);
        $this->get("/wali-kelas/skor/{$student->id}")->assertOk()->assertSee('Tanpa laporan');
    }
}
