<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Evaluasi v1.1: siklus kasus (selesai_at, arsip otomatis 30 hari) dan gambar informasi BK. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_reports', function (Blueprint $t) {
            $t->timestamp('selesai_at')->nullable()->after('opened_at');       // dimulainya hitungan 30 hari menuju arsip
            $t->timestamp('arsip_notified_at')->nullable()->after('selesai_at'); // pemberitahuan sebelum arsip (hari ke-25)
        });
        Schema::table('announcements', function (Blueprint $t) {
            $t->string('image_path')->nullable()->after('isi');
        });

        // Backfill laporan yang sudah selesai agar hitungan arsip berjalan.
        DB::table('incident_reports')->where('status', 'selesai')->whereNull('selesai_at')->update(['selesai_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('incident_reports', fn (Blueprint $t) => $t->dropColumn(['selesai_at', 'arsip_notified_at']));
        Schema::table('announcements', fn (Blueprint $t) => $t->dropColumn('image_path'));
    }
};
