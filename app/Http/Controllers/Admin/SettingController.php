<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModelVersion;
use App\Models\IncidentReport;
use App\Services\AiClassifier;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(AiClassifier $ai)
    {
        return view('admin.pengaturan', [
            'health' => $ai->health(),
            'versions' => AiModelVersion::orderByDesc('id')->get(),
            'current' => AiModelVersion::whereNotNull('deployed_at')->where('versi', '!=', 'belum-dipasang')->latest('id')->first(),
            'classified' => IncidentReport::whereNotNull('ai_priority_suggestion')->count(),
            'endpoint' => config('ai.endpoint'),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'nama_sekolah' => 'required|string|max:120',
            'anon_days' => 'required|integer|min:1|max:365',
            'retensi' => 'required|in:tahun_ajaran,lulus',
            'skor_baik' => 'required|integer|min:2|max:100',
            'skor_perhatian' => 'required|integer|min:1|max:99|lt:skor_baik',
            'skor_peringatan' => 'required|integer|min:1|max:98|lt:skor_perhatian',
            'skor_do' => 'required|integer|min:0|max:97|lt:skor_peringatan',
            'ket_baik' => 'nullable|string|max:200', 'ket_perhatian' => 'nullable|string|max:200',
            'ket_peringatan' => 'nullable|string|max:200', 'ket_kritis' => 'nullable|string|max:200', 'ket_do' => 'nullable|string|max:200',
        ], ['skor_perhatian.lt' => 'Batas Perhatian harus lebih kecil dari batas Baik.', 'skor_peringatan.lt' => 'Batas Peringatan harus lebih kecil dari batas Perhatian.', 'skor_do.lt' => 'Batas DO harus lebih kecil dari batas Peringatan.']);
        foreach ($d as $k => $v) {
            set_setting($k, $v ?? '');
        }
        set_setting('wajib_2fa', $request->boolean('wajib_2fa') ? '1' : '0');
        audit('pengaturan.diubah', null, ['wajib_2fa' => $request->boolean('wajib_2fa')] + $d);
        return back()->with('status', 'Pengaturan disimpan.');
    }
}
