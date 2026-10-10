@extends('layouts.student')
@section('title', 'Skor kredit')
@section('heading', 'Skor kredit')

@section('content')
@php [$lvl, $icon, $cls, $bar] = \App\Support\Ui::score($score); @endphp
<section class="card card-pad">
    <div class="flex items-end justify-between"><p class="text-6xl font-extrabold leading-none">{{ $score }}<span class="text-xl font-bold text-muted">/100</span></p><span class="chip {{ $cls }}"><x-icon :name="$icon" :size="14" />{{ $lvl }}</span></div>
    <div class="bar mt-4 !h-3" role="progressbar" aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100"><span class="{{ $bar }}" style="width: {{ $score }}%"></span></div>
    @php $doLimit = (int) setting('skor_do', 0); $carried = auth()->user()->carriedScore(); @endphp
    @if ($carried !== null)
        <p class="mt-4 flex items-start gap-2 rounded-xl border-2 border-line bg-soft p-3 text-xs font-semibold text-primary-dark"><x-icon name="history" :size="14" class="mt-0.5" />Saldo bawaan dari tahun ajaran sebelumnya: {{ $carried }}. Skor tidak direset saat naik kelas.</p>
    @endif
    @if ($score <= $doLimit + 10)
        <p class="mt-3 flex items-start gap-2 rounded-xl border-2 border-danger/40 bg-danger-soft p-3 text-xs font-semibold text-danger-dark"><x-icon name="alert-triangle" :size="14" class="mt-0.5" />Skormu mendekati atau berada pada batas DO ({{ $doLimit }}). {{ setting('ket_do', 'Skor pada atau di bawah batas ini dapat berujung pada pemberhentian sesuai tata tertib sekolah.') }} Bicarakan dengan guru BK.</p>
    @endif

    <h2 class="mt-5 mb-2 text-sm font-bold">Arti skor</h2>
    <ul class="space-y-2">
        @foreach (\App\Support\Ui::scoreLevels() as [$name, $range, $ket, $ic, $cl])
            <li class="flex items-start gap-3 rounded-xl border-2 border-line p-3"><span class="chip {{ $cl }} shrink-0"><x-icon :name="$ic" :size="14" />{{ $name }}</span><span class="text-xs"><strong class="block">{{ $range }}</strong>{{ $ket }}</span></li>
        @endforeach
        <li class="flex items-start gap-3 rounded-xl border-2 border-line p-3"><span class="chip chip-danger shrink-0"><x-icon name="alert-triangle" :size="14" />Batas DO</span><span class="text-xs"><strong class="block">≤ {{ $doLimit }}</strong>{{ setting('ket_do', 'Skor pada atau di bawah batas ini dapat berujung pada pemberhentian sesuai tata tertib sekolah.') }}</span></li>
    </ul>
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
