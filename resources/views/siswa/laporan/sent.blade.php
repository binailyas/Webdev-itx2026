@extends('layouts.student')
@section('title', 'Laporan terkirim')
@section('bare', '1')

@section('content')
@php $urgent = \App\Models\IncidentReport::where('ticket_code', $ticket)->value('risk_flagged'); @endphp
<div class="flex flex-col items-center pt-10 text-center">
    <span class="mb-5 inline-flex size-20 items-center justify-center rounded-full bg-accent text-ink"><x-icon name="check" :size="40" /></span>
    <h1 class="text-[28px] leading-tight">Laporanmu sudah kami terima</h1>
    <p class="mt-2 text-muted">Kamu bisa cek status kapan saja di dalam akunmu.</p>

    <div class="mt-6 flex w-full items-center gap-3 rounded-xl border-2 border-line bg-soft p-5 text-left"><div class="min-w-0 flex-1"><p class="text-xs font-bold tracking-wider text-muted uppercase">Kode tiket</p><p class="mt-1 font-mono text-3xl font-bold text-primary-dark">{{ $ticket }}</p></div><x-copy-button :text="$ticket" label="Salin kode tiket" /></div>
    <div class="card mt-3 flex w-full items-center gap-3 p-4 text-left">
        <div class="flex-1"><p class="text-xs font-bold tracking-wider text-muted uppercase">PIN 6 digit (tampil sekali)</p><p class="font-mono text-2xl font-bold tracking-widest">{{ $pin }}</p></div>
        <x-copy-button :text="$pin" label="Salin PIN" />
    </div>
    <p class="mt-3 text-xs text-muted">Simpan kode dan PIN untuk mengecek status di menu Laporan.</p>

    {{-- S5: chat dengan BK langsung setelah laporan dikirim (juga untuk akun anonim) --}}
    <form method="post" action="{{ route('siswa.laporan.chat.mulai', $ticket) }}" class="mt-6 w-full">@csrf
        @if ($urgent)
            <button class="btn btn-danger btn-lg btn-block"><x-icon name="phone" :size="20" />Hubungi BK sekarang</button>
        @else
            <button class="btn btn-outline btn-lg btn-block"><x-icon name="message" :size="20" />Chat dengan BK sekarang</button>
        @endif
        <p class="mt-2 text-xs text-muted">Tidak perlu menunggu. Guru BK akan mendapat pemberitahuan.</p>
    </form>

    <div class="mt-4 w-full space-y-3">
        <a href="{{ route('siswa.laporan.show', $ticket) }}" class="btn btn-primary btn-lg btn-block">Lihat status laporan</a>
        <a href="{{ route('siswa.beranda') }}" class="btn btn-secondary btn-lg btn-block">Kembali ke beranda</a>
    </div>
</div>
@endsection
