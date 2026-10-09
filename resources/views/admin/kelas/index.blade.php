@extends('layouts.staff')
@section('title', 'Kelas')
@section('heading', 'Kelas dan naik kelas')
@section('subheading', 'Tahun ajaran ' . $year)

@section('actions')
    <x-btn variant="outline" :href="route('admin.kelas.naik')" icon="arrow-up">Naik kelas massal</x-btn>
    <x-btn variant="primary" icon="plus" @click="$dispatch('open-class')">Tambah kelas</x-btn>
@endsection

@section('content')
<div x-data="{ modal: {{ $errors->any() ? 'true' : 'false' }} }" @open-class.window="modal = true">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($classes as $c)
            <article class="card card-pad">
                <div class="flex items-start justify-between"><h2 class="text-xl">{{ $c->nama_kelas }}</h2><span class="chip chip-gray">Tingkat {{ $c->tingkat }}</span></div>
                <p class="mt-3 flex items-center gap-2 text-sm font-semibold"><x-icon name="users" :size="16" class="text-muted" />{{ $c->students_count }} siswa</p>
                <p class="mt-1.5 flex items-center gap-2 text-sm"><x-icon name="user-check" :size="16" class="text-muted" />
                    @forelse ($c->waliKelas as $w)<a class="font-bold text-primary-dark hover:underline" href="{{ route('admin.staf.show', $w) }}">{{ $w->name }}</a>@empty<span class="chip chip-warn">Belum ada wali kelas</span>@endforelse
                </p>
                @if (! $c->students_count && $c->waliKelas->isEmpty())
                    <form method="post" action="{{ route('admin.kelas.destroy', $c) }}" class="mt-4">@csrf @method('DELETE')<button class="text-xs font-bold text-danger-dark hover:underline">Hapus kelas kosong</button></form>
                @endif
            </article>
        @empty
            <div class="card sm:col-span-2 xl:col-span-3"><x-empty icon="school" title="Belum ada kelas" text="Tambahkan kelas untuk tahun ajaran ini." /></div>
        @endforelse
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-4" role="dialog" aria-modal="true">
        <form method="post" action="{{ route('admin.kelas.store') }}" @click.outside="modal = false" class="w-full max-w-[420px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            @csrf
            <h2 class="mb-4 text-xl">Tambah kelas</h2>
            <div class="space-y-4">
                <x-field name="nama_kelas" label="Nama kelas"><input id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas') }}" class="input" placeholder="contoh: X-4" required></x-field>
                <x-field name="tingkat" label="Tingkat"><select id="tingkat" name="tingkat" class="select">@foreach (['VII','VIII','IX','X','XI','XII'] as $t)<option @selected(old('tingkat') === $t)>{{ $t }}</option>@endforeach</select></x-field>
            </div>
            <div class="mt-5 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
@endsection
