@extends('layouts.student')
@section('title', 'Konsultasi baru')
@section('heading', 'Konsultasi baru')
@section('back', request('dari') === 'beranda' ? route('siswa.beranda') : route('siswa.karir.index'))

@section('content')
<form method="post" action="{{ route('siswa.karir.store') }}" class="space-y-5" x-data="{ topik: @js(old('topik', $topic ?? '')) }">
    @csrf
    <input type="hidden" name="dari" value="{{ request('dari') }}">
    <fieldset>
        <legend class="label">Pilih topik</legend>
        <div class="flex flex-wrap gap-2">
            @foreach ($topics as $t)
                <label class="cursor-pointer"><input type="radio" name="topik" value="{{ $t }}" x-model="topik" class="peer sr-only" required>
                    <span class="chip chip-gray h-10 px-4 text-sm peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white peer-focus-visible:outline-2">{{ $t }}</span></label>
            @endforeach
        </div>
        @error('topik')<p class="error-text">Pilih salah satu topik.</p>@enderror
    </fieldset>
    <x-field name="pesan" label="Apa yang ingin kamu tanyakan?">
        <textarea id="pesan" name="pesan" rows="6" class="textarea" required>{{ old('pesan', $prefill) }}</textarea>
    </x-field>
    <button class="btn btn-primary btn-lg btn-block">Kirim permintaan</button>
</form>
@endsection
