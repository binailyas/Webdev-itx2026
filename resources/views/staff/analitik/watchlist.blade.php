@extends('layouts.staff')
@section('title', 'Watchlist')
@section('heading', 'Watchlist kata pantauan')
@section('actions')<x-btn variant="primary" icon="plus" @click="$dispatch('open-watch')">Tambah pantauan</x-btn>@endsection

@section('content')
@include('staff.analitik._head')
<div x-data="{ modal: false }" @open-watch.window="modal = true">
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($terms as $t)
            @php $pct = min(100, $t->now / max(1, $t->ambang) * 100); $over = $t->now >= $t->ambang; @endphp
            <article class="card card-pad">
                <div class="flex items-start justify-between gap-3"><h3 class="text-lg">{{ $t->term }}</h3>
                    <form method="post" action="{{ sroute('watchlist.destroy', $t) }}">@csrf<button class="btn-icon !size-8 bg-gray-soft !text-muted" aria-label="Hapus {{ $t->term }}"><x-icon name="trash" :size="14" /></button></form></div>
                @if ($t->catatan)<p class="text-xs text-muted">{{ $t->catatan }}</p>@endif
                <div class="mt-3 flex items-center justify-between text-sm font-bold"><span>{{ $t->now }} / {{ $t->ambang }} laporan (30 hari)</span>@if ($over)<span class="chip chip-danger"><x-icon name="alert-triangle" :size="14" />Melewati ambang</span>@endif</div>
                <div class="bar mt-2 !h-3"><span class="{{ $over ? '!bg-danger' : '' }}" style="width: {{ $pct }}%"></span></div>
            </article>
        @empty
            <div class="card md:col-span-2"><x-empty icon="star" title="Belum ada pantauan" text="Tambahkan kata atau nama yang ingin dipantau. Notifikasi dikirim saat jumlahnya melewati ambang." /></div>
        @endforelse
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" action="{{ sroute('watchlist.store') }}" @click.outside="modal = false" class="w-full max-w-[420px] rounded-xl border-2 border-b-4 border-line bg-white p-6">@csrf
            <h2 class="mb-4 text-xl">Tambah pantauan</h2>
            <div class="space-y-4"><x-field name="term" label="Kata atau nama"><input id="term" name="term" class="input" required></x-field>
                <x-field name="ambang" label="Ambang jumlah laporan"><input id="ambang" name="ambang" type="number" min="1" value="5" class="input" required></x-field>
                <x-field name="catatan" label="Catatan (opsional)"><input id="catatan" name="catatan" class="input"></x-field></div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
