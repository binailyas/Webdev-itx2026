<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A2: izin per peran yang dapat diedit admin (menggantikan matriks hardcode). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('role', 30);          // siswa | anonim | admin | bk | wali_kelas
            $t->string('feature', 60);
            $t->char('value', 1)->default('n'); // y | n | r
            $t->timestamps();
            $t->unique(['role', 'feature']);
        });

        // Isi dari default konfigurasi; pertahankan kebijakan lama (app_settings fitur.wk_*) bila ada.
        $now = now();
        $rows = [];
        foreach (\App\Support\Perm::defaults() as $role => $features) {
            foreach ($features as $feature => $value) {
                $rows[] = ['role' => $role, 'feature' => $feature, 'value' => $value, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('role_permissions')->insert($rows);

        if (Schema::hasTable('app_settings')) {
            foreach (['fitur.wk_status' => 'laporan.status', 'fitur.wk_chat' => 'chat.baca', 'fitur.wk_skor' => 'skor.catat'] as $key => $feature) {
                $v = DB::table('app_settings')->where('key', $key)->value('value');
                if ($v === '0') {
                    DB::table('role_permissions')->where(['role' => 'wali_kelas', 'feature' => $feature])->update(['value' => 'n']);
                }
                DB::table('app_settings')->where('key', $key)->delete();
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
