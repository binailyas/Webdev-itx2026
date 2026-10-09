@extends('layouts.plain')
@section('title', 'Bantuan darurat')

@section('content')
<a href="javascript:history.back()" class="btn-icon mb-6 bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
<h1 class="text-[28px] leading-tight">Butuh bantuan sekarang?</h1>
<p class="mt-2 text-muted">Kamu tidak sendiri. Pilih bantuan yang paling sesuai.</p>

<a href="tel:112" class="btn btn-danger btn-lg btn-block mt-6 !h-16 text-base"><x-icon name="phone" :size="22" />Situasi darurat 112</a>

<div class="mt-4 space-y-3">
    <a href="tel:129" class="card flex items-center gap-4 p-4 hover:bg-bg">
        <span class="inline-flex size-12 items-center justify-center rounded-full bg-soft text-primary"><x-icon name="phone" :size="22" /></span>
        <span><span class="block font-bold">SAPA 129 (KemenPPPA)</span><span class="text-xs text-muted">Layanan pengaduan kekerasan terhadap anak dan perempuan</span></span>
    </a>
    <a href="{{ route('login') }}" class="card flex items-center gap-4 p-4 hover:bg-bg">
        <span class="inline-flex size-12 items-center justify-center rounded-full bg-soft text-primary"><x-icon name="heart" :size="22" /></span>
        <span><span class="block font-bold">Hubungi guru BK sekolah</span><span class="text-xs text-muted">Masuk lalu buat laporan atau mulai konsultasi</span></span>
    </a>
</div>
<p class="mt-6 text-center text-sm font-semibold text-muted">Kamu tidak sendiri.</p>
{{-- Verifikasi nomor kontak terbaru sebelum rilis. --}}
@endsection
