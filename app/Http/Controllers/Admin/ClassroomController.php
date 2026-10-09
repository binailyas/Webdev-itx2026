<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\WaliKelasAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    private function year(): string { return setting('tahun_ajaran', Classroom::currentYear()); }

    public function index()
    {
        $classes = Classroom::with('waliKelas')->withCount('students')->where('tahun_ajaran', $this->year())->orderBy('nama_kelas')->get();
        return view('admin.kelas.index', ['classes' => $classes, 'year' => $this->year()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['nama_kelas' => 'required|string|max:20', 'tingkat' => 'required|string|max:5']);
        if (Classroom::where('nama_kelas', $data['nama_kelas'])->where('tahun_ajaran', $this->year())->exists()) {
            return back()->withErrors(['nama_kelas' => 'Kelas ini sudah ada pada tahun ajaran berjalan.'])->withInput();
        }
        Classroom::create($data + ['tahun_ajaran' => $this->year()]);
        audit('kelas.dibuat', null, $data);
        return back()->with('status', 'Kelas ditambahkan.');
    }

    public function destroy(Classroom $classroom)
    {
        if ($classroom->students()->exists() || $classroom->waliKelas()->exists()) {
            return back()->with('error', 'Kelas ini masih memiliki siswa atau wali kelas. Pindahkan dulu sebelum menghapus.');
        }
        $classroom->delete();
        return back()->with('status', 'Kelas dihapus.');
    }

    /** Wizard naik kelas: tahun ajaran → pemetaan kelas → kelas asuhan Wali Kelas → konfirmasi. */
    public function promoteForm()
    {
        $y = (int) substr($this->year(), 0, 4);
        return view('admin.kelas.naik', [
            'year' => $this->year(), 'next' => ($y + 1) . '/' . ($y + 2),
            'classes' => Classroom::with('waliKelas')->withCount('students')->where('tahun_ajaran', $this->year())->orderBy('nama_kelas')->get(),
        ]);
    }

    public function promote(Request $request)
    {
        $data = $request->validate([
            'tahun_baru' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'map' => 'required|array',            // [id kelas lama => nama kelas baru | '' (lulus)]
            'konfirmasi' => 'accepted',
        ], ['konfirmasi.accepted' => 'Centang konfirmasi sebelum memproses naik kelas.']);

        $old = Classroom::where('tahun_ajaran', $this->year())->get()->keyBy('id');

        DB::transaction(function () use ($data, $old) {
            $ta = $data['tahun_baru'];
            $newIds = [];
            foreach ($data['map'] as $oldId => $newName) {
                $oldC = $old[$oldId] ?? null;
                if (! $oldC) continue;
                $newName = trim((string) $newName);
                if ($newName === '') {
                    // Lulus: siswa dilepas dari kelas dan dinonaktifkan.
                    $ids = StudentProfile::where('classroom_id', $oldId)->pluck('user_id');
                    StudentProfile::where('classroom_id', $oldId)->update(['classroom_id' => null]);
                    \App\Models\User::whereIn('id', $ids)->update(['is_active' => false]);
                    continue;
                }
                $new = Classroom::firstOrCreate(['nama_kelas' => $newName, 'tahun_ajaran' => $ta], ['tingkat' => strtok($newName, '-')]);
                $newIds[$oldId] = $new->id;
                StudentProfile::where('classroom_id', $oldId)->update(['classroom_id' => $new->id, 'tahun_ajaran' => $ta]);
            }
            // Perbarui kelas asuhan Wali Kelas mengikuti pemetaan.
            foreach (WaliKelasAssignment::whereIn('classroom_id', array_keys($newIds))->get() as $a) {
                WaliKelasAssignment::firstOrCreate(['user_id' => $a->user_id, 'classroom_id' => $newIds[$a->classroom_id]], ['tahun_ajaran' => $ta, 'created_at' => now()]);
                $a->delete();
            }
            set_setting('tahun_ajaran', $ta);
        });

        audit('naik_kelas', null, ['tahun_baru' => $data['tahun_baru']]);
        return redirect()->route('admin.kelas.index')->with('status', 'Naik kelas selesai. Skor kredit siswa kembali 100 pada tahun ajaran ' . $data['tahun_baru'] . '.');
    }
}
