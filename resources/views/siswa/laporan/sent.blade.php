@extends('layouts.student')
@section('title', 'Laporan terkirim')
@section('bare', '1')

@section('content')
<div class="flex flex-col items-center pt-10 text-center" x-data="{ copied: false }">
    <span class="mb-5 inline-flex size-20 items-center justify-center rounded-full bg-accent text-ink"><x-icon name="check" :size="40" /></span>
    <h1 class="text-[28px] leading-tight">Laporanmu sudah kami terima</h1>
    <p class="mt-2 text-muted">Kamu bisa cek status kapan saja.</p>

    <div class="mt-6 w-full rounded-xl border-2 border-line bg-soft p-5"><p class="text-xs font-bold tracking-wider text-muted uppercase">Kode tiket</p><p class="mt-1 font-mono text-4xl font-bold text-primary-dark">{{ $ticket }}</p></div>
    <div class="card mt-3 flex w-full items-center gap-3 p-4 text-left">
        <div class="flex-1"><p class="text-xs font-bold tracking-wider text-muted uppercase">PIN 6 digit (tampil sekali)</p><p class="font-mono text-2xl font-bold tracking-widest">{{ $pin }}</p></div>
        <button type="button" class="btn-icon bg-primary" aria-label="Salin PIN" @click="navigator.clipboard.writeText('{{ $pin }}'); copied = true"><x-icon name="copy" :size="18" /></button>
    </div>
    <p x-show="copied" x-cloak class="mt-2 text-sm font-semibold text-accent-text" role="status">Tersalin</p>
    <p class="mt-3 text-xs text-muted">Simpan kode dan PIN. Dengan keduanya kamu bisa cek status tanpa masuk akun.</p>

    <div class="mt-8 w-full space-y-3">
        <a href="{{ route('siswa.laporan.show', $ticket) }}" class="btn btn-primary btn-lg btn-block">Lihat status laporan</a>
        <a href="{{ route('siswa.beranda') }}" class="btn btn-outline btn-lg btn-block">Kembali ke beranda</a>
    </div>
</div>
@endsection
