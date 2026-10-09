@extends('layouts.student')
@section('title', 'Skor kredit')
@section('heading', 'Skor kredit')

@section('content')
@php [$lvl, $icon, $cls, $bar] = \App\Support\Ui::score($score); @endphp
<section class="card card-pad">
    <div class="flex items-end justify-between"><p class="text-6xl font-extrabold leading-none">{{ $score }}<span class="text-xl font-bold text-muted">/100</span></p><span class="chip {{ $cls }}"><x-icon :name="$icon" :size="14" />{{ $lvl }}</span></div>
    <div class="bar mt-4 !h-3" role="progressbar" aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100"><span class="{{ $bar }}" style="width: {{ $score }}%"></span></div>
    <ul class="mt-4 grid grid-cols-2 gap-2 text-xs font-semibold text-muted">
        <li>≥ 90 Baik</li><li>70–89 Perhatian</li><li>50–69 Peringatan</li><li>&lt; 50 Kritis</li>
    </ul>
    <p class="mt-3 text-xs text-muted">Skor dihitung ulang tiap tahun ajaran.</p>
</section>

<h2 class="mt-7 mb-3 text-lg">Catatan pengurangan</h2>
<div class="space-y-3">
    @forelse ($records as $r)
        <a href="{{ route('siswa.kredit.show', $r) }}" class="card card-pad block hover:bg-bg">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="font-bold {{ $r->isVoided() ? 'text-muted line-through' : '' }}">{{ $r->category->name }}</p><p class="text-xs text-muted">{{ $r->tanggal?->translatedFormat('d M Y') }} · {{ $r->recorder->name }}, {{ $r->recorder->role->label }}</p></div>
                <span class="chip {{ $r->isVoided() ? 'chip-gray line-through' : 'chip-danger' }}">−{{ $r->poin_dikurangi }}</span>
            </div>
            <p class="mt-2 text-sm {{ $r->isVoided() ? 'text-muted line-through' : '' }}">{{ $r->alasan }}</p>
            @if ($r->isVoided())<span class="chip chip-gray mt-2">Dibatalkan</span>@endif
        </a>
    @empty
        <div class="card"><x-empty icon="check-circle" title="Belum ada catatan" text="Skormu masih 100." /></div>
    @endforelse
</div>
@endsection
