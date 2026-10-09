<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\InfoController;
use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Kelola informasi BK (khusus Guru BK). */
class AnnouncementController extends Controller
{
    public function index()
    {
        return view('staff.informasi.index', ['items' => Announcement::latest('id')->get(), 'cats' => InfoController::CATEGORIES]);
    }

    public function create() { return view('staff.informasi.form', ['a' => new Announcement(['status' => 'draf']), 'cats' => InfoController::CATEGORIES]); }

    public function edit(Announcement $announcement) { return view('staff.informasi.form', ['a' => $announcement, 'cats' => InfoController::CATEGORIES]); }

    private function rules(): array
    {
        return ['judul' => 'required|string|max:150', 'kategori' => 'required|in:' . implode(',', array_keys(InfoController::CATEGORIES)),
            'isi' => 'required|string|max:10000', 'aksi' => 'required|in:draf,terbit', 'jadwal' => 'nullable|date'];
    }

    public function store(Request $request)
    {
        $d = $request->validate($this->rules());
        $a = new Announcement(['user_id' => $request->user()->id, 'slug' => $this->slug($d['judul'])]);
        return $this->save($a, $d);
    }

    public function update(Request $request, Announcement $announcement)
    {
        return $this->save($announcement, $request->validate($this->rules()));
    }

    private function slug(string $t, ?int $ignore = null): string
    {
        $base = Str::slug($t) ?: 'informasi'; $s = $base; $i = 2;
        while (Announcement::where('slug', $s)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) $s = $base . '-' . $i++;
        return $s;
    }

    private function save(Announcement $a, array $d)
    {
        $wasPublished = $a->status === 'terbit';
        $a->fill(['judul' => $d['judul'], 'kategori' => $d['kategori'], 'isi' => $d['isi'], 'status' => $d['aksi']]);
        if (! $a->exists) {
            $a->slug = $this->slug($d['judul']);
        }
        $a->published_at = $d['aksi'] === 'terbit' ? ($d['jadwal'] ? \Carbon\Carbon::parse($d['jadwal']) : ($a->published_at ?? now())) : $a->published_at;
        $a->save();

        if ($d['aksi'] === 'terbit' && ! $wasPublished) {
            User::where('role_id', Role::idOf('siswa'))->where('is_active', true)->pluck('id')->each(fn ($id) => Notifier::to($id, 'info', ['pesan' => 'Informasi baru dari BK: ' . $a->judul]));
        }
        return redirect()->route('bk.informasi.index')->with('status', $d['aksi'] === 'terbit' ? 'Informasi diterbitkan.' : 'Draf disimpan.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('bk.informasi.index')->with('status', 'Informasi dihapus.');
    }
}
