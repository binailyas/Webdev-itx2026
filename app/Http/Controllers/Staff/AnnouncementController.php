<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\InfoController;
use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Kelola informasi BK (khusus Guru BK), termasuk unggah gambar (B2). */
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
        return [
            'judul' => 'required|string|max:150',
            'kategori' => 'required|in:' . implode(',', array_keys(InfoController::CATEGORIES)),
            'isi' => 'required|string|max:10000',
            'aksi' => 'required|in:draf,terbit',
            'jadwal' => 'nullable|date',
            // B2: hanya gambar raster (tanpa SVG agar tidak ada skrip tertanam), maks. 2 MB.
            'gambar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'hapus_gambar' => 'nullable|boolean',
        ];
    }

    private const MESSAGES = [
        'gambar.image' => 'Berkas harus berupa gambar (JPG, PNG, atau WebP).',
        'gambar.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
        'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
        'gambar.dimensions' => 'Dimensi gambar terlalu besar (maksimal 6000 piksel).',
    ];

    public function store(Request $request)
    {
        $d = $request->validate($this->rules(), self::MESSAGES);
        return $this->save($request, new Announcement(['user_id' => $request->user()->id]), $d);
    }

    public function update(Request $request, Announcement $announcement)
    {
        return $this->save($request, $announcement, $request->validate($this->rules(), self::MESSAGES));
    }

    private function slug(string $t, ?int $ignore = null): string
    {
        $base = Str::slug($t) ?: 'informasi'; $s = $base; $i = 2;
        while (Announcement::where('slug', $s)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) $s = $base . '-' . $i++;
        return $s;
    }

    private function save(Request $request, Announcement $a, array $d)
    {
        $wasPublished = $a->status === 'terbit';
        $a->fill(['judul' => $d['judul'], 'kategori' => $d['kategori'], 'isi' => $d['isi'], 'status' => $d['aksi']]);
        if (! $a->exists) {
            $a->slug = $this->slug($d['judul']);
        }

        if ($request->hasFile('gambar')) {
            $old = $a->image_path;
            // Nama acak dari ekstensi hasil deteksi isi berkas (bukan nama unggahan).
            $a->image_path = $request->file('gambar')->storePublicly('informasi', 'public');
            $old && Storage::disk('public')->delete($old);
        } elseif ($request->boolean('hapus_gambar') && $a->image_path) {
            Storage::disk('public')->delete($a->image_path);
            $a->image_path = null;
        }

        $a->published_at = $d['aksi'] === 'terbit' ? (! empty($d['jadwal']) ? \Carbon\Carbon::parse($d['jadwal']) : ($a->published_at ?? now())) : $a->published_at;
        $a->save();

        if ($d['aksi'] === 'terbit' && ! $wasPublished) {
            User::where('role_id', Role::idOf('siswa'))->where('is_active', true)->pluck('id')->each(fn ($id) => Notifier::to($id, 'info', ['pesan' => 'Informasi baru dari BK: ' . $a->judul]));
        }
        return redirect()->route('bk.informasi.index')->with('status', $d['aksi'] === 'terbit' ? 'Informasi diterbitkan.' : 'Draf disimpan.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->image_path && Storage::disk('public')->delete($announcement->image_path);
        $announcement->delete();
        return redirect()->route('bk.informasi.index')->with('status', 'Informasi dihapus.');
    }
}
