<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\KeywordAlias;
use App\Models\KeywordStopword;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

/** Kamus alias, stopword, dan pengaturan ekstraksi (khusus BK; Wali Kelas memakai watchlist). */
class DictionaryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->hasRole('wali_kelas')) {
            return redirect()->route('wk.analitik.watchlist');
        }
        return view('staff.pengaturan', [
            'tab' => in_array($request->query('tab'), ['stopword', 'ekstraksi'], true) ? $request->query('tab') : 'alias',
            'aliases' => KeywordAlias::with('student.studentProfile.classroom')->orderBy('alias')->get(),
            'stopwords' => KeywordStopword::orderBy('word')->get(),
            'students' => User::where('role_id', Role::idOf('siswa'))->orderBy('name')->get(['id', 'name']),
            'extract' => setting('ekstraksi_chat', '1') === '1',
        ]);
    }

    private function bkOnly(): void { abort_unless(auth()->user()->hasRole('bk'), 403); }

    public function aliasStore(Request $request)
    {
        $this->bkOnly();
        $d = $request->validate(['alias' => 'required|string|max:60', 'student_user_id' => 'required|exists:users,id']);
        $student = User::findOrFail($d['student_user_id']);
        KeywordAlias::create(['alias' => trim($d['alias']), 'canonical' => mb_strtolower(explode(' ', $student->name)[0]), 'student_user_id' => $student->id, 'dibuat_oleh' => $request->user()->id]);
        audit('analitik.alias_dibuat', $student, ['alias' => $d['alias']]);
        return back()->with('status', 'Alias ditambahkan.');
    }

    public function aliasDestroy(KeywordAlias $alias)
    {
        $this->bkOnly();
        $alias->delete();
        return back()->with('status', 'Alias dihapus.');
    }

    public function stopStore(Request $request)
    {
        $this->bkOnly();
        $d = $request->validate(['word' => 'required|string|max:40']);
        KeywordStopword::firstOrCreate(['word' => mb_strtolower(trim($d['word']))], ['scope' => 'sekolah']);
        return redirect()->route('bk.pengaturan.index', ['tab' => 'stopword'])->with('status', 'Kata diabaikan ditambahkan.');
    }

    public function stopDestroy(KeywordStopword $word)
    {
        $this->bkOnly();
        $word->delete();
        return redirect()->route('bk.pengaturan.index', ['tab' => 'stopword']);
    }

    /** Pulihkan bawaan: hapus kata tambahan sekolah. */
    public function stopReset()
    {
        $this->bkOnly();
        KeywordStopword::where('scope', 'sekolah')->delete();
        return redirect()->route('bk.pengaturan.index', ['tab' => 'stopword'])->with('status', 'Daftar dipulihkan ke bawaan.');
    }

    public function extraction(Request $request)
    {
        $this->bkOnly();
        set_setting('ekstraksi_chat', $request->boolean('ekstraksi_chat') ? '1' : '0');
        return redirect()->route('bk.pengaturan.index', ['tab' => 'ekstraksi'])->with('status', 'Pengaturan ekstraksi disimpan.');
    }
}
