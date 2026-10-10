<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemeriksaan hak akses berbasis tabel role_permissions (diedit admin), dengan
 * fallback ke nilai default di config/permissions.php bila baris belum ada.
 */
class Perm
{
    /** @var array<string, array<string, string>>|null  [role => [feature => y|n|r]] */
    private static ?array $cache = null;

    /** @var array<string, true>|null  peran yang matriksnya dikunci */
    private static ?array $locks = null;

    public static function flush(): void
    {
        self::$cache = null;
        self::$locks = null;
    }

    /** A1: apakah kolom peran ini dikunci (tidak bisa diubah di matriks)? */
    public static function locked(string $role): bool
    {
        if (self::$locks === null) {
            self::$locks = [];
            try {
                if (Schema::hasTable('role_locks')) {
                    foreach (DB::table('role_locks')->pluck('role') as $r) {
                        self::$locks[$r] = true;
                    }
                }
            } catch (\Throwable) {
            }
        }
        return isset(self::$locks[$role]);
    }

    /** Izin untuk nama peran (termasuk "anonim" bagi akun anonim). */
    public static function allowsRole(string $role, string $feature): bool
    {
        return self::value($role, $feature) !== 'n';
    }

    /** Nilai default dari konfigurasi. */
    public static function defaults(): array
    {
        $roles = array_keys(config('permissions.roles'));
        $out = [];
        foreach (config('permissions.groups') as $features) {
            foreach ($features as $key => $row) {
                foreach ($roles as $i => $role) {
                    $out[$role][$key] = $row[$i + 1];
                }
            }
        }
        return $out;
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $map = self::defaults();
        try {
            if (Schema::hasTable('role_permissions')) {
                foreach (DB::table('role_permissions')->get() as $r) {
                    $map[$r->role][$r->feature] = $r->value;
                }
            }
        } catch (\Throwable) {
            // basis data belum siap: pakai default
        }
        return self::$cache = $map;
    }

    /** Nilai sel: y | n | r. */
    public static function value(string $role, string $feature): string
    {
        return self::load()[$role][$feature] ?? 'n';
    }

    public static function allows(?User $user, string $feature): bool
    {
        return $user && $user->role && self::value($user->role->name, $feature) !== 'n';
    }

    /** Hanya "y" (bukan baca-saja). */
    public static function writes(?User $user, string $feature): bool
    {
        return $user && $user->role && self::value($user->role->name, $feature) === 'y';
    }

    /** Sel bermakna bagi peran ini (fitur memang tersedia untuknya), terlepas dari kunci? */
    public static function applicable(string $role, string $feature): bool
    {
        $default = self::defaults()[$role][$feature] ?? 'n';
        return $default !== 'n' || in_array($role, (config('permissions.grantable') ?? [])[$feature] ?? [], true);
    }

    /** Apakah sel [peran, fitur] boleh diubah admin? */
    public static function editable(string $role, string $feature): bool
    {
        if (! array_key_exists($role, config('permissions.roles')) || self::locked($role)) {
            return false;
        }
        return self::applicable($role, $feature);
    }
}
