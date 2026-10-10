@extends('layouts.student')
@section('title', 'Laporan ' . $r->ticket_code)
@section('heading', 'Laporan ' . $r->ticket_code)
@section('back', route('siswa.laporan.index'))

@section('content')
@php
    // Timeline 4 langkah: Dikirim → Ditinjau → Diproses → Selesai.
    $order = ['baru' => 0, 'ditinjau' => 1, 'diproses' => 2, 'selesai' => 3];
    $closed = in_array($r->status, ['ditolak', 'diarsipkan'], true);
    $cur = $order[$r->status] ?? ($r->status === 'diarsipkan' ? 3 : 1);
    $steps = [['baru', 'Dikirim'], ['ditinjau', 'Ditinjau'], ['diproses', 'Diproses'], [$closed && $r->status === 'ditolak' ? 'ditolak' : 'selesai', $r->status === 'ditolak' ? 'Ditolak' : 'Selesai']];
    $byStatus = $r->histories->groupBy('status_to')->map->last();
@endphp
<div class="mb-2 flex items-center gap-2 text-sm"><span class="text-xs font-bold tracking-wider text-muted uppercase">Kode tiket</span><span class="font-mono font-bold text-primary-dark">{{ $r->ticket_code }}</span><x-copy-button :text="$r->ticket_code" label="Salin kode tiket" /></div>
<div class="mb-4 flex items-center justify-between"><x-status-chip :status="$r->status" /><span class="text-xs text-muted">Dikirim {{ $r->created_at->translatedFormat('d M Y, H:i') }}</span></div>

<section class="card card-pad">
    <ol class="space-y-0">
        @foreach ($steps as $i => [$key, $label])
            @php $state = $i < $cur || ($i === $cur && in_array($r->status, ['selesai', 'ditolak', 'diarsipkan'])) ? 'done' : ($i === $cur ? 'now' : 'next'); $h = $byStatus[$key] ?? ($i === 0 ? $byStatus['baru'] ?? null : null); @endphp
            <li class="relative flex gap-4 pb-6 last:pb-0">
                @unless ($loop->last)<span class="absolute top-7 left-[11px] h-full w-0.5 {{ $state === 'done' ? 'bg-accent' : 'bg-line' }}"></span>@endunless
                <span class="relative z-10 mt-1 inline-flex size-6 shrink-0 items-center justify-center rounded-full {{ ['done' => 'bg-accent text-ink', 'now' => 'bg-primary text-white ring-4 ring-soft', 'next' => 'bg-line'][$state] }}">@if ($state === 'done')<x-icon name="check" :size="14" />@endif</span>
                <div><p class="font-bold {{ $state === 'next' ? 'text-muted' : '' }}">{{ $label }}</p>
                    @if ($h)<p class="text-xs text-muted">{{ $h->created_at->translatedFormat('d M Y, H:i') }}</p>@if ($h->alasan)<p class="mt-1 text-sm">{{ $h->alasan }}</p>@endif
                    @elseif ($state === 'now')<p class="text-sm">BK sedang menelaah laporanmu.</p>@endif</div>
            </li>
        @endforeach
    </ol>
</section>

<section class="card card-pad mt-4 space-y-3 text-sm">
    <h2 class="text-lg">Ringkasan laporan</h2>
    @foreach ([['Kategori', $r->category->name], ['Judul', $r->judul], ['Tanggal kejadian', $r->tanggal_kejadian?->translatedFormat('d M Y') ?? '—'], ['Lokasi', $r->lokasi ?: '—']] as [$k, $v])
        <div class="flex justify-between gap-4 border-b-2 border-line/60 pb-2 last:border-0"><span class="text-muted">{{ $k }}</span><span class="text-right font-semibold">{{ $v }}</span></div>
    @endforeach
    <div class="flex items-center justify-between"><span class="text-muted">Prioritas</span><x-priority-chip :priority="$r->prioritas" /></div>
</section>

@if ($r->chatRoom)
    <a href="{{ route('siswa.laporan.chat', $r->ticket_code) }}" class="btn btn-primary btn-lg btn-block mt-4 relative"><x-icon name="message" :size="20" />Buka chat @if ($unread)<span class="num-badge num-badge-danger" aria-label="{{ $unread }} pesan baru">{{ $unread }}</span>@endif</a>
@else
    {{-- S5/G4.4: siswa (termasuk anonim) dapat langsung membuka chat dengan BK tanpa menunggu. --}}
    <form method="post" action="{{ route('siswa.laporan.chat.mulai', $r->ticket_code) }}" class="mt-4">@csrf
        @if ($r->risk_flagged || $r->prioritas === 'tinggi')
            <button class="btn btn-danger btn-lg btn-block"><x-icon name="phone" :size="20" />Hubungi BK sekarang</button>
        @else
            <button class="btn btn-primary btn-lg btn-block"><x-icon name="message" :size="20" />Chat dengan BK</button>
        @endif
        <p class="mt-2 text-center text-xs text-muted">{{ request()->attributes->get('is_anon') ? 'Kamu tetap anonim di chat.' : 'Guru BK akan membalas di chat ini.' }}</p>
    </form>
@endif
@endsection
