<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

if (! function_exists('settings_cache')) {
    /** Cache pengaturan per-proses; $reset=true memuat ulang dari basis data. */
    function settings_cache(bool $reset = false): array
    {
        static $cache = null;
        if ($reset || $cache === null) {
            $cache = AppSetting::query()->pluck('value', 'key')->all();
        }
        return $cache;
    }
}

if (! function_exists('setting')) {
    /** Baca pengaturan aplikasi (tabel app_settings). */
    function setting(string $key, mixed $default = null): mixed
    {
        $cache = settings_cache();
        return array_key_exists($key, $cache) && $cache[$key] !== null ? $cache[$key] : $default;
    }
}

if (! function_exists('set_setting')) {
    function set_setting(string $key, mixed $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        settings_cache(true);   // segera terbaca di request yang sama
    }
}

if (! function_exists('audit')) {
    /** Catat tindakan sensitif ke audit_logs. */
    function audit(string $action, ?object $entity = null, array $data = []): void
    {
        AuditLog::create([
            'user_id' => Auth::guard('web')->id(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'data' => $data ?: null,
            'created_at' => now(),
        ]);
    }
}

if (! function_exists('sroute')) {
    /** URL rute staf sesuai peran aktif (bk.* atau wk.*). */
    function sroute(string $name, mixed $params = [], bool $absolute = true): string
    {
        $prefix = Auth::user()?->hasRole('wali_kelas') ? 'wk' : 'bk';
        return route("$prefix.$name", $params, $absolute);
    }
}

if (! function_exists('fmt_num')) {
    function fmt_num(int|float $n): string { return number_format($n, 0, ',', '.'); }
}
