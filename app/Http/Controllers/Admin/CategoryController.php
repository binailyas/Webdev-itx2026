<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditCategory;
use App\Models\CreditRecord;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use Illuminate\Http\Request;

/** A11 — kategori insiden dan kategori pelanggaran skor (hanya pengurangan). */
class CategoryController extends Controller
{
    private function model(string $type): string
    {
        return match ($type) { 'insiden' => IncidentCategory::class, 'skor' => CreditCategory::class, default => abort(404) };
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'skor' ? 'skor' : 'insiden';
        return view('admin.kategori', [
            'tab' => $tab,
            'insiden' => IncidentCategory::orderBy('urutan')->get(),
            'skor' => CreditCategory::orderBy('poin_pengurangan_default')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->validate(['type' => 'required|in:insiden,skor'])['type'];
        if ($type === 'insiden') {
            $d = $request->validate(['name' => 'required|string|max:80', 'description' => 'nullable|string|max:255']);
            IncidentCategory::create($d + ['urutan' => IncidentCategory::max('urutan') + 1]);
        } else {
            $d = $request->validate(['name' => 'required|string|max:80', 'poin' => 'required|integer|min:1|max:100']);
            CreditCategory::create(['name' => $d['name'], 'poin_pengurangan_default' => $d['poin']]);
        }
        audit('kategori.dibuat', null, ['jenis' => $type, 'nama' => $d['name']]);
        return redirect()->route('admin.kategori.index', ['tab' => $type])->with('status', 'Kategori ditambahkan.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $m = $this->model($type)::findOrFail($id);
        if ($type === 'insiden') {
            $d = $request->validate(['name' => 'required|string|max:80', 'description' => 'nullable|string|max:255', 'urutan' => 'nullable|integer|min:0']);
            $m->update($d + ['is_active' => $request->boolean('is_active')]);
        } else {
            $d = $request->validate(['name' => 'required|string|max:80', 'poin' => 'required|integer|min:1|max:100']);
            $m->update(['name' => $d['name'], 'poin_pengurangan_default' => $d['poin'], 'is_active' => $request->boolean('is_active')]);
        }
        audit('kategori.diubah', $m);
        return redirect()->route('admin.kategori.index', ['tab' => $type])->with('status', 'Kategori disimpan.');
    }

    public function destroy(string $type, int $id)
    {
        $m = $this->model($type)::findOrFail($id);
        $used = $type === 'insiden' ? IncidentReport::where('category_id', $id)->exists() : CreditRecord::where('category_id', $id)->exists();
        if ($used) {
            $m->update(['is_active' => false]);
            return back()->with('status', 'Kategori sudah dipakai, jadi dinonaktifkan (bukan dihapus).');
        }
        audit('kategori.dihapus', $m);
        $m->delete();
        return back()->with('status', 'Kategori dihapus.');
    }
}
