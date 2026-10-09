<?php

namespace App\Services;

use App\Models\AnonymousAccount;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;

/** Notifikasi dalam aplikasi (tabel notifications). Badge dihitung langsung dari data laporan. */
class Notifier
{
    public static function to(User|AnonymousAccount|int $who, string $type, array $data = []): void
    {
        $row = ['type' => $type, 'data' => $data];
        if ($who instanceof AnonymousAccount) {
            $row['anon_id'] = $who->id;
        } else {
            $row['user_id'] = $who instanceof User ? $who->id : $who;
        }
        UserNotification::create($row);
    }

    public static function toRole(string $role, string $type, array $data = []): void
    {
        User::where('role_id', Role::idOf($role))->where('is_active', true)->pluck('id')
            ->each(fn ($id) => self::to($id, $type, $data));
    }

    /** Wali Kelas yang mengampu salah satu kelas pada daftar. */
    public static function toWaliKelasOf(Collection|array $classroomIds, string $type, array $data = []): void
    {
        \App\Models\WaliKelasAssignment::whereIn('classroom_id', $classroomIds)->pluck('user_id')->unique()
            ->each(fn ($id) => self::to($id, $type, $data));
    }

    public static function label(string $type): array
    {
        return match ($type) {
            'laporan_baru' => ['Laporan baru', 'file-text', 'bg-primary'],
            'darurat' => ['Prioritas darurat', 'alert-triangle', 'bg-danger'],
            'ai_flagged' => ['Saran AI: berisiko tinggi', 'bot', 'bg-warning !text-ink'],
            'kelas_saya' => ['Kelas saya terlibat', 'home', 'bg-primary-dark'],
            'chat' => ['Pesan chat baru', 'message', 'bg-accent !text-ink'],
            'karir' => ['Sesi karir baru', 'compass', 'bg-accent !text-ink'],
            'status' => ['Status laporan berubah', 'refresh', 'bg-primary'],
            'skor' => ['Pengurangan skor', 'star', 'bg-danger'],
            'info' => ['Informasi baru', 'megaphone', 'bg-accent !text-ink'],
            'watchlist' => ['Watchlist melewati ambang', 'eye', 'bg-warning !text-ink'],
            'catatan' => ['Catatan baru', 'file-text', 'bg-primary'],
            default => ['Pemberitahuan', 'bell', 'bg-primary'],
        };
    }
}
