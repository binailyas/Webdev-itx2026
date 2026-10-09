@extends('layouts.student')
@section('title', 'Cek status laporan')
@section('heading', 'Cek status laporan')
@section('back', route('siswa.beranda'))

@section('content')
<p class="mb-5 text-sm text-muted">Masukkan kode tiket dan PIN 6 digit yang kamu terima saat mengirim laporan.</p>

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
    <a href="{{ route('siswa.cekstatus') }}" class="btn btn-outline btn-block mt-4">Cek tiket lain</a>
@else
    <form method="post" action="{{ route('siswa.cekstatus.hasil') }}" class="space-y-4">
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
