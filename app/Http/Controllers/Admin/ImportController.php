<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** A06 — impor siswa massal (CSV): unggah → cocokkan kolom → tinjau → selesai. */
class ImportController extends Controller
{
    private const FIELDS = ['name' => 'Nama lengkap', 'nis' => 'NIS', 'kelas' => 'Kelas', 'angkatan' => 'Angkatan', 'email' => 'Email (opsional)'];

    public function index(Request $request)
    {
        $token = $request->query('t');
        $step = (int) $request->query('langkah', 1);
        $state = ['step' => 1];

        if ($token && Storage::disk('local')->exists("imports/$token.csv")) {
            [$header, $rows] = $this->read($token);
            $state = ['step' => max(2, min($step, 4)), 'token' => $token, 'header' => $header, 'count' => count($rows), 'sample' => array_slice($rows, 0, 3)];
            if ($state['step'] >= 3 && $request->session()->has("import.$token.map")) {
                $state += $this->validateRows($rows, $request->session()->get("import.$token.map"), $header);
            } elseif ($state['step'] >= 3) {
                $state['step'] = 2;
            }
            $state['guess'] = $this->guess($header);
        }

        return view('admin.impor', ['s' => $state, 'fields' => self::FIELDS, 'done' => $request->session()->get('import.done')]);
    }

    public function template()
    {
        $csv = "nama,nis,kelas,angkatan,email\nRani Putri,2025001,X-1,2025,\nBudi Hartono,2025002,X-2,2025,budi@contoh.sch.id\n";
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="templat-impor-siswa.csv"']);
    }

    public function preview(Request $request)
    {
        // Langkah 1 (unggah) atau 2 (pemetaan kolom).
        if ($request->hasFile('berkas')) {
            $request->validate(['berkas' => 'required|file|mimes:csv,txt|max:4096'], ['berkas.mimes' => 'Berkas harus berformat CSV. Unduh templat untuk contoh.']);
            $token = Str::random(24);
            Storage::disk('local')->putFileAs('imports', $request->file('berkas'), "$token.csv");
            return redirect()->route('admin.impor.index', ['t' => $token, 'langkah' => 2]);
        }

        $token = $request->validate(['t' => 'required|string', 'map' => 'required|array'])['t'];
        abort_unless(Storage::disk('local')->exists("imports/$token.csv"), 404);
        $map = array_map('intval', array_filter($request->input('map'), fn ($v) => $v !== '' && $v !== null));
        if (! isset($map['name'], $map['nis'], $map['kelas'])) {
            return back()->with('error', 'Cocokkan minimal kolom Nama, NIS, dan Kelas.');
        }
        $request->session()->put("import.$token.map", $map);
        return redirect()->route('admin.impor.index', ['t' => $token, 'langkah' => 3]);
    }

    public function commit(Request $request)
    {
        $token = $request->validate(['t' => 'required|string'])['t'];
        abort_unless(Storage::disk('local')->exists("imports/$token.csv"), 404);
        [$header, $rows] = $this->read($token);
        $res = $this->validateRows($rows, $request->session()->get("import.$token.map", []), $header);

        $created = [];
        DB::transaction(function () use ($res, &$created) {
            foreach ($res['valid'] as $r) {
                $pw = StudentController::randomPassword();
                $u = User::create(['name' => $r['name'], 'email' => $r['email'] ?: $r['nis'] . '@siswa.local', 'password' => $pw, 'role_id' => Role::idOf('siswa'), 'is_active' => true]);
                StudentProfile::create(['user_id' => $u->id, 'nis' => $r['nis'], 'classroom_id' => $r['classroom_id'], 'angkatan' => $r['angkatan'], 'tahun_ajaran' => setting('tahun_ajaran')]);
                $created[] = [$r['nis'], $r['name'], $pw];
            }
        });
        audit('impor.selesai', null, ['dibuat' => count($created), 'bermasalah' => count($res['errors'])]);
        Storage::disk('local')->delete("imports/$token.csv");

        $request->session()->put('import.done', ['count' => count($created), 'errors' => count($res['errors'])]);
        $request->session()->put('import.passwords', $created);
        return redirect()->route('admin.impor.index', ['langkah' => 4]);
    }

    /** Unduh daftar kata sandi awal — hanya sekali. */
    public function passwords(Request $request)
    {
        $rows = $request->session()->pull('import.passwords');
        abort_unless($rows, 404);
        $csv = "nis,nama,kata_sandi_awal\n" . collect($rows)->map(fn ($r) => implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', $c) . '"', $r)))->implode("\n");
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="kata-sandi-awal.csv"']);
    }

    // ---------------------------------------------------------------

    private function read(string $token): array
    {
        $h = fopen(Storage::disk('local')->path("imports/$token.csv"), 'r');
        $first = fgets($h);
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        rewind($h);
        $header = array_map(fn ($c) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $c)), fgetcsv($h, 0, $delim) ?: []);
        $rows = [];
        while (($r = fgetcsv($h, 0, $delim)) !== false) {
            if (array_filter($r, fn ($c) => trim((string) $c) !== '')) {
                $rows[] = $r;
            }
        }
        fclose($h);
        return [$header, $rows];
    }

    private function guess(array $header): array
    {
        $alias = ['name' => ['nama', 'name', 'nama lengkap'], 'nis' => ['nis', 'nisn'], 'kelas' => ['kelas', 'class'], 'angkatan' => ['angkatan', 'tahun masuk'], 'email' => ['email', 'surel']];
        $g = [];
        foreach ($alias as $f => $names) {
            foreach ($header as $i => $h) {
                if (in_array(mb_strtolower($h), $names, true)) {
                    $g[$f] = $i;
                }
            }
        }
        return $g;
    }

    private function validateRows(array $rows, array $map, array $header): array
    {
        $classes = Classroom::where('tahun_ajaran', setting('tahun_ajaran', Classroom::currentYear()))->get()->keyBy(fn ($c) => mb_strtolower($c->nama_kelas));
        $existing = StudentProfile::pluck('nis')->flip();
        $seen = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $i => $r) {
            $line = $i + 2;
            $get = fn ($f) => isset($map[$f]) ? trim((string) ($r[$map[$f]] ?? '')) : '';
            $row = ['name' => $get('name'), 'nis' => $get('nis'), 'kelas' => $get('kelas'), 'angkatan' => $get('angkatan') ?: null, 'email' => $get('email') ?: null];
            $err = [];

            if ($row['name'] === '') $err[] = 'Nama kosong';
            if ($row['nis'] === '') $err[] = 'NIS kosong';
            elseif (isset($existing[$row['nis']]) || isset($seen[$row['nis']])) $err[] = 'NIS ganda';
            $cls = $classes[mb_strtolower($row['kelas'])] ?? null;
            if (! $cls) $err[] = 'Kelas tidak ditemukan';
            if ($row['email'] && (! filter_var($row['email'], FILTER_VALIDATE_EMAIL) || User::where('email', $row['email'])->exists())) $err[] = 'Email tidak valid atau sudah dipakai';

            if ($err) {
                $errors[] = ['line' => $line, 'nis' => $row['nis'], 'name' => $row['name'], 'msg' => implode(', ', $err)];
            } else {
                $seen[$row['nis']] = true;
                $valid[] = $row + ['classroom_id' => $cls->id];
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }
}
