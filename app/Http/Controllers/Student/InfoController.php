<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    public const CATEGORIES = ['karir' => 'Karir', 'kesehatan-mental' => 'Kesehatan mental', 'anti-perundungan' => 'Anti-perundungan', 'beasiswa' => 'Beasiswa'];

    public function index(Request $request)
    {
        $q = Announcement::published()->latest('published_at');
        if ($c = $request->query('kategori')) $q->where('kategori', $c);
        if ($s = trim((string) $request->query('q'))) $q->where(fn ($w) => $w->where('judul', 'like', "%$s%")->orWhere('isi', 'like', "%$s%"));
        $items = $q->get();

        return view('siswa.informasi.index', ['items' => $items, 'cats' => self::CATEGORIES]);
    }

    public function show(string $slug)
    {
        $a = Announcement::published()->where('slug', $slug)->with('author')->firstOrFail();
        return view('siswa.informasi.show', ['a' => $a, 'cats' => self::CATEGORIES]);
    }
}
