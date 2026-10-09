@extends('layouts.staff')
@section('title', 'Kata: ' . $keyword)
@section('heading', 'Kata “' . $keyword . '”')
@section('subheading', $reports->count() . ' laporan tertaut pada filter ini. Indikasi, bukan bukti.')
@section('actions')<x-btn variant="secondary" :href="sroute('analitik.kata', request()->only('periode', 'kategori', 'prioritas', 'status', 'kelas'))" icon="chevron-left">Peringkat kata kunci</x-btn>@endsection

@section('content')
<div class="space-y-3">
    @forelse ($reports as $r)
        <a href="{{ sroute('laporan.show', $r) }}" class="card card-pad block hover:bg-bg">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-sm font-bold text-primary-dark">{{ $r->ticket_code }}</span>
                <x-status-chip :status="$r->status" /><x-priority-chip :priority="$r->prioritas" />
                <span class="chip chip-gray">{{ $r->category->name }}</span>
                <span class="ml-auto text-xs text-muted">{{ $r->created_at->translatedFormat('d M Y') }}</span>
            </div>
            <p class="mt-2 font-bold">{{ $r->judul }}</p>
            <p class="mt-1 rounded-lg bg-bg p-2 text-xs leading-relaxed text-muted">{{ $snippet($r) }}</p>
        </a>
    @empty
        <div class="card"><x-empty icon="file-text" title="Tidak ada laporan tertaut" text="Coba perluas periode atau hapus filter." /></div>
    @endforelse
</div>
@endsection
