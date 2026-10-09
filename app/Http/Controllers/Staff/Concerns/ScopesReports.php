<?php

namespace App\Http\Controllers\Staff\Concerns;

use App\Models\Classroom;
use App\Models\IncidentReport;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cakupan data staf. BK melihat semua. Wali Kelas: semua laporan untuk kesadaran
 * situasional di daftar laporan, tetapi analitik/ringkasan/skor default ke kelas asuhan;
 * akses lintas kelas butuh alasan (tercatat di audit log).
 */
trait ScopesReports
{
    protected function isWk(): bool { return auth()->user()->hasRole('wali_kelas'); }

    /** ID kelas asuhan Wali Kelas (kosong untuk BK). */
    protected function myClassIds(): array
    {
        return $this->isWk() ? auth()->user()->classroomIds() : [];
    }

    /** Wali Kelas sedang dalam mode "semua kelas" (setelah memberi alasan)? */
    protected function allClassesMode(): bool
    {
        return $this->isWk() && session()->has('wk_semua_kelas');
    }

    /** Apakah analitik/ringkasan dibatasi ke kelas asuhan? */
    protected function scoped(): bool
    {
        return $this->isWk() && ! $this->allClassesMode();
    }

    /** Terapkan cakupan kelas pada query laporan (analitik/ringkasan). */
    protected function scopeQuery(Builder $q): Builder
    {
        if ($this->scoped()) {
            $ids = $this->myClassIds();
            // Tanpa kelas asuhan => tidak ada data (bukan semua data).
            $ids ? $q->involvingClassrooms($ids) : $q->whereRaw('1 = 0');
        }
        return $q;
    }

    protected function classChips()
    {
        return Classroom::whereIn('id', $this->myClassIds())->orderBy('nama_kelas')->get();
    }

    /** Filter umum analitik/ringkasan: periode, kategori, prioritas, status, kelas. */
    protected function filteredReports(\Illuminate\Http\Request $r): Builder
    {
        $q = $this->scopeQuery(IncidentReport::query());
        if (! $this->isWk()) {
            $q->visibleToBk();   // B2
        }
        $days = (int) $r->query('periode', 30);
        if ($days > 0) {
            $q->where('created_at', '>=', now()->subDays($days)->startOfDay());
        }
        if ($r->filled('kategori')) $q->where('category_id', $r->query('kategori'));
        if ($r->filled('prioritas')) $q->where('prioritas', $r->query('prioritas'));
        if ($r->filled('status')) $q->where('status', $r->query('status'));
        if ($r->filled('kelas') && ! $this->scoped()) $q->involvingClassrooms([(int) $r->query('kelas')]);
        return $q;
    }
}
