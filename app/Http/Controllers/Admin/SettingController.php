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
        ]);
        foreach ($d as $k => $v) {
            set_setting($k, $v);
        }
        set_setting('wajib_2fa', $request->boolean('wajib_2fa') ? '1' : '0');
        audit('pengaturan.diubah', null, ['wajib_2fa' => $request->boolean('wajib_2fa')] + $d);
        return back()->with('status', 'Pengaturan disimpan.');
    }
}
