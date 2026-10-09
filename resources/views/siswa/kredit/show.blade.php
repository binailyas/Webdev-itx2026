@extends('layouts.student')
@section('title', 'Catatan pengurangan')
@section('heading', 'Detail catatan')
@section('back', route('siswa.kredit'))

@section('content')
<section class="card card-pad space-y-3 text-sm">
    @foreach ([['Kategori', $rec->category->name], ['Bobot', '−' . $rec->poin_dikurangi], ['Tanggal', $rec->tanggal?->translatedFormat('d M Y') ?? '—'], ['Dicatat oleh', $rec->recorder->name . ', ' . $rec->recorder->role->label], ['Laporan terkait', $rec->report?->ticket_code ?? '—']] as [$k, $v])
        <div class="flex justify-between gap-4 border-b-2 border-line/60 pb-2 last:border-0"><span class="text-muted">{{ $k }}</span><span class="text-right font-semibold">{{ $v }}</span></div>
    @endforeach
    @if ($rec->isVoided())<span class="chip chip-gray">Dibatalkan · {{ $rec->void_reason }}</span>@endif
</section>
<section class="card card-pad mt-4"><h2 class="text-base">Alasan</h2><p class="mt-2 text-sm">{{ $rec->alasan }}</p></section>
<a href="{{ route('siswa.karir.create', ['topik' => 'Lainnya', 'pesan' => 'Saya ingin meminta klarifikasi tentang catatan pengurangan ' . $rec->category->name . ' tanggal ' . $rec->tanggal?->format('d/m/Y') . '.']) }}" class="btn btn-outline btn-lg btn-block mt-4">Ajukan klarifikasi ke BK</a>
@endsection
