<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Evaluasi v1.2: prioritas pilihan siswa (terpisah dari saran AI dan prioritas akhir). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $t) {
            $t->string('prioritas_siswa', 10)->default('rendah')->after('prioritas');
        });

        // A1: kunci matriks per peran (peran terkunci tidak bisa diubah sampai kuncinya dibuka).
        Schema::create('role_locks', function (Blueprint $t) {
            $t->string('role', 30)->primary();
            $t->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('locked_at')->nullable();
        });

        // A2: rapikan urutan kategori yang sudah ada (hilangkan duplikat/celah).
        $i = 0;
        foreach (DB::table('incident_categories')->orderBy('urutan')->orderBy('id')->pluck('id') as $id) {
            DB::table('incident_categories')->where('id', $id)->update(['urutan' => ++$i]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_locks');
        Schema::table('incident_reports', fn (Blueprint $t) => $t->dropColumn('prioritas_siswa'));
    }
};
