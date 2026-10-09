@extends('layouts.plain')
@section('title', 'Cek status laporan')

@section('content')
<a href="{{ route('welcome') }}" class="btn-icon mb-6 bg-soft !text-primary-dark" aria-label="Kembali"><x-icon name="chevron-left" :size="20" /></a>
<h1 class="text-[28px] leading-tight">Cek status laporan</h1>
<p class="mt-2 mb-6 text-muted">Masukkan kode tiket dan PIN 6 digit yang kamu terima saat mengirim laporan.</p>

@if ($report)
    <div class="card card-pad">
        <div class="flex items-center justify-between"><p class="font-mono text-xl font-bold">{{ $report->ticket_code }}</p><x-status-chip :status="$report->status" /></div>
        <ol class="mt-5 space-y-4 border-l-2 border-line pl-5">
            @foreach ($report->histories as $h)
                <li class="relative">
                    <span class="absolute top-1.5 -left-[27px] size-3 rounded-full {{ $loop->last ? 'bg-primary' : 'bg-accent' }}"></span>
                    <p class="text-sm font-bold">{{ \App\Support\Ui::status($h->status_to)[0] }}</p>
                    <p class="text-xs text-muted">{{ $h->created_at->translatedFormat('d M Y, H:i') }}</p>
                    @if ($h->alasan)<p class="mt-1 text-sm">{{ $h->alasan }}</p>@endif
                </li>
            @endforeach
        </ol>
    </div>
    <a href="{{ route('status.check') }}" class="btn btn-outline btn-block mt-4">Cek tiket lain</a>
@else
    <form method="post" action="{{ route('status.check.result') }}" class="space-y-4">
        @csrf
        <x-field name="ticket" label="Kode tiket">
            <input id="ticket" name="ticket" value="{{ old('ticket') }}" class="input font-mono uppercase @error('ticket') input-error @enderror" placeholder="BK-0231" required>
        </x-field>
        <x-field name="pin" label="PIN 6 digit">
            <input id="pin" name="pin" inputmode="numeric" maxlength="6" class="input font-mono" placeholder="482915" required>
        </x-field>
        <button class="btn btn-primary btn-lg btn-block">Lihat status</button>
    </form>
@endif
@endsection
