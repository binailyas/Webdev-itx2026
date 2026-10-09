<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain 1 — Pengguna & Autentikasi.
 * users (bawaan Laravel) dilengkapi kolom BK; ditambah roles, classrooms,
 * student_profiles, wali_kelas_assignments, anonymous_accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();        // siswa | admin | bk | wali_kelas
            $t->string('label');
            $t->string('guard_name')->default('web');
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->after('id')->constrained('roles');
            $t->boolean('is_active')->default(true)->after('password');
            $t->boolean('two_factor_enabled')->default(false)->after('is_active');
            $t->timestamp('last_login_at')->nullable()->after('two_factor_enabled');
        });

        Schema::create('classrooms', function (Blueprint $t) {
            $t->id();
            $t->string('nama_kelas');
            $t->string('tingkat');               // X | XI | XII | VII ...
            $t->string('tahun_ajaran');          // 2025/2026
            $t->timestamps();
            $t->unique(['nama_kelas', 'tahun_ajaran']);
        });

        Schema::create('student_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('nis')->unique();
            $t->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $t->string('angkatan')->nullable();
            $t->string('tahun_ajaran')->nullable();
            $t->unsignedSmallInteger('skor_awal')->default(100);
            $t->timestamps();
        });

        Schema::create('wali_kelas_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $t->string('tahun_ajaran');
            $t->timestamp('created_at')->nullable();
            $t->unique(['user_id', 'classroom_id']);
        });

        // Akun anonim: sengaja TANPA user_id, IP, atau user_agent.
        Schema::create('anonymous_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('alias')->unique();
            $t->string('password_hash');
            $t->string('remember_token', 100)->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('last_activity_at')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_accounts');
        Schema::dropIfExists('wali_kelas_assignments');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('classrooms');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn(['is_active', 'two_factor_enabled', 'last_login_at']);
        });
        Schema::dropIfExists('roles');
    }
};
