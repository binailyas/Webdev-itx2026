<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain 2 — Laporan Insiden, Domain 3 — AI Klasifikasi Prioritas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_model_versions', function (Blueprint $t) {
            $t->id();
            $t->string('versi');
            $t->timestamp('deployed_at')->nullable();
            $t->float('f1_score')->nullable();
            $t->float('precision_score')->nullable();
            $t->float('recall_score')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamps();
        });

        Schema::create('incident_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('incident_reports', function (Blueprint $t) {
            $t->id();
            $t->string('ticket_code')->unique();
            $t->string('pin_hash')->nullable();          // PIN cek status (kode tiket + PIN)
            $t->foreignId('category_id')->constrained('incident_categories');
            $t->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('reporter_anon_id')->nullable()->constrained('anonymous_accounts')->nullOnDelete();
            $t->string('judul');
            $t->text('kronologi');
            $t->date('tanggal_kejadian')->nullable();
            $t->string('lokasi')->nullable();
            $t->text('pihak_terlibat')->nullable();
            $t->enum('prioritas', ['rendah', 'sedang', 'tinggi'])->default('sedang');
            $t->boolean('risk_flagged')->default(false);   // kata berisiko terdeteksi (self-harm/ancaman)
            $t->enum('status', ['baru', 'ditinjau', 'diproses', 'selesai', 'ditolak', 'diarsipkan'])->default('baru');
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // PIC (BK)
            // Saran AI (M10)
            $t->enum('ai_priority_suggestion', ['rendah', 'sedang', 'tinggi'])->nullable();
            $t->float('ai_priority_confidence')->nullable();
            $t->boolean('ai_flagged')->default(false);
            $t->foreignId('ai_model_version_id')->nullable()->constrained('ai_model_versions')->nullOnDelete();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'prioritas']);
        });

        Schema::create('report_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->string('file_path');
            $t->string('file_name');
            $t->string('mime_type')->nullable();
            $t->unsignedInteger('file_size')->default(0);
            $t->timestamps();
        });

        Schema::create('report_status_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status_from')->nullable();
            $t->string('status_to');
            $t->text('alasan')->nullable();
            $t->timestamps();
        });

        Schema::create('report_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->text('isi');
            $t->boolean('penting')->default(false);
            $t->timestamps();
        });

        Schema::create('report_keywords', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->string('keyword');
            $t->unsignedTinyInteger('n')->default(1);
            $t->unsignedInteger('frekuensi')->default(1);
            $t->enum('source', ['laporan', 'chat'])->default('laporan');
            $t->timestamps();
            $t->index('keyword');
        });

        Schema::create('report_entities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->string('nama_entitas');
            $t->text('konteks')->nullable();
            $t->enum('jenis_entitas', ['terlapor', 'korban', 'saksi', 'lainnya'])->nullable();
            $t->foreignId('user_id_terkait')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('kandidat_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->enum('status', ['saran', 'terkonfirmasi', 'ditolak'])->default('saran');
            $t->boolean('dikonfirmasi')->default(false);
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ai_overrides', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('incident_reports')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users');
            $t->enum('ai_suggestion', ['rendah', 'sedang', 'tinggi'])->nullable();
            $t->enum('priority_set', ['rendah', 'sedang', 'tinggi']);
            $t->text('alasan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ai_overrides', 'report_entities', 'report_keywords', 'report_notes', 'report_status_histories',
                  'report_attachments', 'incident_reports', 'incident_categories', 'ai_model_versions'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
