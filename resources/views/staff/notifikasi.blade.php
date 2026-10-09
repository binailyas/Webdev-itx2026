@extends('layouts.staff')
@section('title', 'Notifikasi')
@section('heading', 'Notifikasi')
@section('actions')
    <div class="tabs"><a href="?tab=semua" class="tab" @if (! $unreadOnly) aria-current="page" @endif>Semua</a><a href="?tab=belum" class="tab" @if ($unreadOnly) aria-current="page" @endif>Belum dibaca</a></div>
    <form method="post" action="{{ sroute('notifikasi.baca') }}">@csrf<button class="btn btn-secondary btn-sm">Tandai semua dibaca</button></form>
@endsection

@section('content')
<div class="max-w-3xl">
@forelse ($groups as $label => $items)
    <h2 class="mt-4 mb-2 text-xs font-bold tracking-wider text-muted uppercase">{{ $label }}</h2>
    <div class="card divide-y-2 divide-line/60 overflow-hidden">
        @foreach ($items as $n)
            @php [$t, $ic, $bg] = \App\Services\Notifier::label($n->type); $rid = $n->data['report_id'] ?? null; $room = $n->data['room_id'] ?? null; @endphp
            <div class="flex items-start gap-3 p-4">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-white {{ $bg }}"><x-icon :name="$ic" :size="18" /></span>
                <div class="min-w-0 flex-1"><p class="text-sm font-bold">{{ $t }}</p><p class="text-sm text-muted">{{ $n->data['pesan'] ?? '' }}</p><p class="text-[11px] text-muted">{{ $n->created_at->diffForHumans() }}</p></div>
                @if ($rid)<a href="{{ sroute('laporan.show', $rid) }}" class="btn btn-secondary btn-sm">Buka</a>@elseif ($room)<a href="{{ route('bk.karir.show', $room) }}" class="btn btn-secondary btn-sm">Buka</a>@endif
                @unless ($n->read_at)<span class="mt-2 size-2.5 rounded-full bg-primary" aria-label="Belum dibaca"></span>@endunless
            </div>
        @endforeach
    </div>
@empty
    <div class="card"><x-empty icon="bell" title="Belum ada notifikasi" /></div>
@endforelse
</div>
@endsection
