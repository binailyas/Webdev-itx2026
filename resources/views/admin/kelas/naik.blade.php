@extends('layouts.staff')
@section('title', 'Naik kelas massal')
@section('heading', 'Naik kelas massal')
@section('subheading', 'Wizard 3 langkah untuk pergantian tahun ajaran.')

@section('actions')<x-btn variant="secondary" :href="route('admin.kelas.index')" icon="chevron-left">Kembali</x-btn>@endsection

@section('content')
@php
    // Saran pemetaan: X-n -> XI-n, XI-n -> XII-n, XII-* -> lulus.
    $suggest = function ($name) {
        if (preg_match('/^XII-/', $name)) return '';
        if (preg_match('/^XI-(.+)$/', $name, $m)) return 'XII-' . $m[1];
        if (preg_match('/^X-(.+)$/', $name, $m)) return 'XI-' . $m[1];
        return $name;
    };
@endphp
<form method="post" action="{{ route('admin.kelas.naik.proses') }}" x-data="{ step: 1 }" class="space-y-6">
    @csrf
    <ol class="flex flex-wrap items-center gap-3">
        @foreach (['Pilih tahun ajaran', 'Pemetaan kelas', 'Kelas asuhan dan konfirmasi'] as $i => $l)
            <li class="flex items-center gap-2"><span class="inline-flex size-9 items-center justify-center rounded-full border-2 text-sm font-bold" :class="step > {{ $i + 1 }} ? 'border-accent bg-accent text-ink' : (step === {{ $i + 1 }} ? 'border-primary-dark bg-primary text-white' : 'border-line bg-white text-muted')">{{ $i + 1 }}</span><span class="text-sm font-bold">{{ $l }}</span></li>
        @endforeach
    </ol>

    <section x-show="step === 1" class="card card-pad">
        <h2 class="text-lg">Tahun ajaran baru</h2>
        <p class="mb-4 text-sm text-muted">Sekarang: {{ $year }}.</p>
        <x-field name="tahun_baru" label="Tahun ajaran tujuan"><input id="tahun_baru" name="tahun_baru" class="input max-w-xs font-mono" value="{{ old('tahun_baru', $next) }}" required></x-field>
    </section>

    <section x-show="step === 2" x-cloak class="card card-pad">
        <h2 class="mb-1 text-lg">Pemetaan kelas lama ke baru</h2>
        <p class="mb-4 text-sm text-muted">Kosongkan nama kelas baru untuk menandai siswa lulus (akun dinonaktifkan).</p>
        <div class="space-y-3">
            @foreach ($classes as $c)
                <div class="grid items-center gap-3 sm:grid-cols-[160px_32px_1fr_120px]">
                    <span class="chip chip-gray w-fit">{{ $c->nama_kelas }}</span>
                    <x-icon name="arrow-right" :size="18" class="hidden text-muted sm:block" />
                    <input name="map[{{ $c->id }}]" value="{{ old('map.' . $c->id, $suggest($c->nama_kelas)) }}" placeholder="(lulus)" class="input" aria-label="Kelas baru untuk {{ $c->nama_kelas }}">
                    <span class="text-xs text-muted">{{ $c->students_count }} siswa</span>
                </div>
            @endforeach
        </div>
    </section>

    <section x-show="step === 3" x-cloak class="space-y-4">
        <div class="card card-pad">
            <h2 class="mb-1 text-lg">Perbarui kelas asuhan Wali Kelas</h2>
            <p class="mb-3 text-sm text-muted">Penetapan kelas asuhan ikut berpindah mengikuti pemetaan agar tetap relevan.</p>
            <ul class="space-y-2 text-sm">
                @foreach ($classes->filter(fn ($c) => $c->waliKelas->isNotEmpty()) as $c)
                    <li class="flex flex-wrap items-center gap-2"><span class="chip chip-gray">{{ $c->nama_kelas }}</span><x-icon name="arrow-right" :size="14" class="text-muted" /><span class="chip chip-soft">{{ $suggest($c->nama_kelas) ?: 'lulus' }}</span><span class="text-muted">· {{ $c->waliKelas->pluck('name')->implode(', ') }}</span></li>
                @endforeach
            </ul>
        </div>
        <div class="flex gap-3 rounded-xl border-2 border-warning bg-warning/15 p-4 text-sm font-semibold"><x-icon name="alert-triangle" :size="20" />Skor kredit TIDAK direset: sisa skor tahun ajaran ini terbawa sebagai saldo bawaan ke tahun ajaran baru, dan tercatat di riwayat.</div>
        <label class="flex cursor-pointer items-center gap-3 text-sm font-bold"><input type="checkbox" name="konfirmasi" value="1" class="check"> Saya sudah memeriksa pemetaan dan ingin memproses</label>
        @error('konfirmasi')<p class="error-text">{{ $message }}</p>@enderror
        @error('tahun_baru')<p class="error-text">{{ $message }}</p>@enderror
    </section>

    <div class="flex justify-between">
        <button type="button" class="btn btn-secondary" x-show="step > 1" @click="step--">Sebelumnya</button><span x-show="step === 1"></span>
        <button type="button" class="btn btn-primary" x-show="step < 3" @click="step++">Lanjut</button>
        <button class="btn btn-danger" x-show="step === 3" x-cloak>Proses naik kelas</button>
    </div>
</form>
@endsection
