<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    /** Terjemahan jenis tindakan -> [label, kelas chip]. */
    public static function action(string $a): array
    {
        return match (true) {
            str_starts_with($a, 'login.gagal') => ['Login gagal', 'chip-danger'],
            str_starts_with($a, 'login') => ['Login', 'chip-gray'],
            str_contains($a, 'dihapus') => ['Hapus', 'chip-danger'],
            str_starts_with($a, 'role') || str_starts_with($a, 'kelas') || str_starts_with($a, 'tugas') => ['Akses', 'chip-soft'],
            str_starts_with($a, 'akun') || str_starts_with($a, 'impor') || $a === 'naik_kelas' => ['Akun', 'chip-mint'],
            str_starts_with($a, 'laporan') || str_starts_with($a, 'ai') || str_starts_with($a, 'skor') => ['Kasus', 'chip-warn'],
            str_starts_with($a, 'analitik') => ['Analitik', 'chip-warn'],
            default => ['Sistem', 'chip-gray'],
        };
    }

    private function query(Request $r)
    {
        $q = AuditLog::with('user')->latest('created_at')->latest('id');
        if ($r->filled('aktor')) $q->where('user_id', $r->query('aktor'));
        if ($r->filled('jenis')) $q->where('action', 'like', $r->query('jenis') . '%');
        if ($r->filled('dari')) $q->whereDate('created_at', '>=', $r->query('dari'));
        if ($r->filled('sampai')) $q->whereDate('created_at', '<=', $r->query('sampai'));
        return $q;
    }

    public function index(Request $request)
    {
        if ($request->boolean('ekspor')) {
            $rows = $this->query($request)->limit(5000)->get();
            $csv = "waktu,aktor,tindakan,target,data\n" . $rows->map(fn ($l) => implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', [
                $l->created_at, $l->user?->name ?? 'Sistem', $l->action, trim(($l->entity_type ?? '') . ' ' . ($l->entity_id ?? '')), json_encode($l->data, JSON_UNESCAPED_UNICODE),
            ])))->implode("\n");
            audit('audit.ekspor');
            return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="audit-log.csv"']);
        }

        return view('admin.audit', [
            'logs' => $this->query($request)->paginate(15)->withQueryString(),
            'actors' => User::whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'bk', 'wali_kelas']))->orderBy('name')->get(['id', 'name']),
            'types' => ['login' => 'Login', 'akun' => 'Akun', 'role' => 'Role', 'kelas' => 'Kelas asuhan', 'laporan' => 'Laporan', 'ai' => 'Saran AI', 'skor' => 'Skor', 'analitik' => 'Analitik', 'impor' => 'Impor'],
        ]);
    }
}
