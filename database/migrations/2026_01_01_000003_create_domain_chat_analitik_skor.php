<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain 4 — Chat, 5 — Analitik Kata Kunci, 6 — Skor Kredit, Konten & Sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Domain 4: Chat ----------
        Schema::create('chat_rooms', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['insiden', 'karir']);
            $t->foreignId('report_id')->nullable()->unique()->constrained('incident_reports')->cascadeOnDelete();
            $t->foreignId('career_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->string('topik')->nullable();
            $t->enum('status', ['menunggu', 'berlangsung', 'selesai'])->default('menunggu');
            $t->text('ringkasan')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('is_readonly')->default(false);
            $t->boolean('wk_diizinkan')->default(false);   // BK mengizinkan Wali Kelas membaca
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('chat_participants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('chat_room_id')->constrained('chat_rooms')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->foreignId('anon_id')->nullable()->constrained('anonymous_accounts')->cascadeOnDelete();
            $t->string('role');                    // pelapor | bk | wali_kelas
            $t->boolean('can_write')->default(true);
            $t->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('chat_room_id')->constrained('chat_rooms')->cascadeOnDelete();
            $t->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('sender_anon_id')->nullable()->constrained('anonymous_accounts')->nullOnDelete();
            $t->text('isi');
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });

        // ---------- Domain 5: Analitik ----------
        Schema::create('keyword_aliases', function (Blueprint $t) {
            $t->id();
            $t->string('alias');
            $t->string('canonical');
            $t->foreignId('student_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('keyword_stopwords', function (Blueprint $t) {
            $t->id();
            $t->string('word')->unique();
            $t->enum('scope', ['bawaan', 'sekolah'])->default('bawaan');
            $t->timestamps();
        });

        Schema::create('keyword_daily_stats', function (Blueprint $t) {
            $t->id();
            $t->string('keyword');
            $t->date('tanggal');
            $t->foreignId('category_id')->nullable()->constrained('incident_categories')->nullOnDelete();
            $t->unsignedInteger('frekuensi')->default(0);
            $t->timestamps();
            $t->index(['keyword', 'tanggal']);
        });

        Schema::create('watchlist_terms', function (Blueprint $t) {
            $t->id();
            $t->string('term');
            $t->text('catatan')->nullable();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedInteger('ambang')->default(5);
            $t->boolean('notifikasi')->default(true);
            $t->timestamp('last_alerted_at')->nullable();
            $t->timestamps();
        });

        // ---------- Domain 6: Skor, Konten, Sistem ----------
        Schema::create('credit_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedSmallInteger('poin_pengurangan_default');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('credit_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained('users')->cascadeOnDelete();   // user siswa
            $t->foreignId('user_id_pencatat')->constrained('users');
            $t->foreignId('report_id')->nullable()->constrained('incident_reports')->nullOnDelete();
            $t->foreignId('category_id')->constrained('credit_categories');
            $t->unsignedSmallInteger('poin_dikurangi');
            $t->text('alasan');
            $t->date('tanggal')->nullable();
            $t->string('tahun_ajaran');
            $t->timestamp('voided_at')->nullable();
            $t->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('void_reason')->nullable();
            $t->timestamps();
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->string('judul');
            $t->string('slug')->unique();
            $t->string('kategori');                 // karir | kesehatan-mental | anti-perundungan | beasiswa
            $t->text('isi');
            $t->enum('status', ['draf', 'terbit'])->default('draf');
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $t->foreignId('anon_id')->nullable()->constrained('anonymous_accounts')->cascadeOnDelete();
            $t->string('type');
            $t->json('data')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action');
            $t->string('entity_type')->nullable();
            $t->unsignedBigInteger('entity_id')->nullable();
            $t->json('data')->nullable();
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('app_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['app_settings', 'audit_logs', 'notifications', 'announcements', 'credit_records', 'credit_categories',
                  'watchlist_terms', 'keyword_daily_stats', 'keyword_stopwords', 'keyword_aliases',
                  'chat_messages', 'chat_participants', 'chat_rooms'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
