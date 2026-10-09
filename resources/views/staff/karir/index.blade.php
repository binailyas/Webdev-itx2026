@extends('layouts.staff')
@section('title', 'Konsultasi karir')
@section('heading', 'Antrean konsultasi karir')

@section('content')
<div class="tabs">
    @foreach (['menunggu' => 'Menunggu', 'berlangsung' => 'Berlangsung', 'selesai' => 'Selesai'] as $k => $l)
        <a href="?tab={{ $k }}" class="tab" @if ($tab === $k) aria-current="page" @endif>{{ $l }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    @forelse ($rooms as $room)
        @php $first = $room->messages->first(); $waitDays = $room->created_at->diffInDays(now()); @endphp
        <article class="card card-pad">
            <div class="flex items-center gap-3"><x-avatar :name="$room->student->name" :size="44" />
                <div class="min-w-0 flex-1"><p class="font-bold">{{ $room->student->name }}</p><p class="text-xs text-muted">Kelas {{ $room->student->studentProfile?->classroom?->nama_kelas ?? '—' }}</p></div>
                <span class="chip chip-soft">{{ $room->topik }}</span></div>
            <p class="mt-3 line-clamp-2 text-sm">{{ $first?->isi }}</p>
            <div class="mt-4 flex items-center justify-between">
                <span class="chip {{ $tab === 'menunggu' && $waitDays >= 1 ? 'chip-warn' : 'chip-gray' }}"><x-icon name="clock" :size="14" />{{ $tab === 'menunggu' ? 'Menunggu ' : '' }}{{ \App\Support\Ui::age($room->created_at) }}</span>
                @if ($tab === 'menunggu')<form method="post" action="{{ route('bk.karir.accept', $room) }}">@csrf<button class="btn btn-primary btn-sm">Terima sesi</button></form>
                @else<a href="{{ route('bk.karir.show', $room) }}" class="btn btn-outline btn-sm">Buka</a>@endif
            </div>
        </article>
    @empty
        <div class="card lg:col-span-2"><x-empty icon="compass" title="Antrean kosong" text="Tidak ada sesi pada tab ini." /></div>
    @endforelse
</div>
@endsection
