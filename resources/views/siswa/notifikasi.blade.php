@extends('layouts.student')
@section('title', 'Notifikasi')
@section('heading', 'Notifikasi')
@section('back', route('siswa.beranda'))
@section('header-actions')
    @if ($unread)<form method="post" action="{{ route('siswa.notifikasi.baca') }}">@csrf<button class="text-xs font-bold text-primary-dark">Tandai semua dibaca</button></form>@endif
@endsection

@section('content')
@forelse ($groups as $label => $items)
    <h2 class="mt-5 mb-2 text-xs font-bold tracking-wider text-muted uppercase">{{ $label }}</h2>
    <div class="space-y-2">
        @foreach ($items as $n)
            @php [$t, $ic, $bg] = \App\Services\Notifier::label($n->type); @endphp
            <div class="card flex items-start gap-3 p-3">
                <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-white {{ $bg }}"><x-icon :name="$ic" :size="18" /></span>
                <div class="min-w-0 flex-1"><p class="text-sm font-bold">{{ $t }}</p><p class="text-sm text-muted">{{ $n->data['pesan'] ?? '' }}</p><p class="mt-0.5 text-[11px] text-muted">{{ $n->created_at->diffForHumans() }}</p></div>
                @unless ($n->read_at)<span class="mt-2 size-2.5 rounded-full bg-primary" aria-label="Belum dibaca"></span>@endunless
            </div>
        @endforeach
    </div>
@empty
    <div class="card mt-4"><x-empty icon="bell" title="Belum ada notifikasi" /></div>
@endforelse
@endsection
