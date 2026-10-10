@extends('layouts.student')
@section('title', 'Konsultasi karir')
@section('heading', 'Konsultasi karir')

@section('content')
<img src="{{ asset('images/career.webp') }}" alt="" width="1400" height="781" class="mb-4 h-auto w-full rounded-xl">
<div class="tabs mb-4">
    <a href="?tab=berlangsung" class="tab" @if ($tab === 'berlangsung') aria-current="page" @endif>Berlangsung</a>
    <a href="?tab=selesai" class="tab" @if ($tab === 'selesai') aria-current="page" @endif>Selesai</a>
</div>
<div class="space-y-3">
    @forelse ($rooms as $r)
        @php $bkId = $r->participants->firstWhere('role', 'bk')?->user_id; @endphp
        <a href="{{ route('siswa.karir.show', $r) }}" class="card card-pad block hover:bg-bg">
            <div class="flex items-center justify-between gap-2"><span class="chip chip-soft">{{ $r->topik }}</span>
                @if ($r->status === 'menunggu')<span class="chip chip-warn"><x-icon name="clock" :size="14" />Menunggu</span>@elseif ($r->status === 'berlangsung')<span class="chip chip-soft"><x-icon name="message" :size="14" />Berlangsung</span>@else<span class="chip chip-mint"><x-icon name="check-circle" :size="14" />Selesai</span>@endif</div>
            <p class="mt-2 text-sm text-muted">{{ $r->created_at->translatedFormat('d M Y') }}@if ($bkId && isset($bk[$bkId])) · {{ $bk[$bkId] }}@endif</p>
        </a>
    @empty
        <div class="card"><x-empty icon="compass" title="Belum ada sesi" text="Tanyakan jurusan, kuliah, atau beasiswa." /></div>
    @endforelse
</div>
<a href="{{ route('siswa.karir.create') }}" class="btn btn-primary btn-lg btn-block mt-5"><x-icon name="plus" :size="20" />Mulai konsultasi baru</a>
@endsection
