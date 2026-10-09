<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CreditRecord;
use Illuminate\Http\Request;

/** Skor kredit siswa: hanya pengurangan; tidak ada penambahan atau penghargaan. */
class CreditController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $records = $u->creditRecords()->with(['category', 'recorder.role'])->latest('tanggal')->latest('id')->get();

        return view('siswa.kredit.index', ['score' => $u->creditScore(), 'records' => $records]);
    }

    public function show(Request $request, CreditRecord $record)
    {
        abort_unless($record->student_id === $request->user()->id, 404);
        $record->load(['category', 'recorder.role', 'report']);
        return view('siswa.kredit.show', ['rec' => $record]);
    }
}
