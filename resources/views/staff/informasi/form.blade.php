@extends('layouts.staff')
@section('title', $a->exists ? 'Ubah informasi' : 'Tulis informasi')
@section('heading', $a->exists ? 'Ubah informasi' : 'Tulis informasi')
@section('actions')<x-btn variant="secondary" :href="route('bk.informasi.index')" icon="chevron-left">Kembali</x-btn>@endsection

@section('content')
<form method="post" action="{{ $a->exists ? route('bk.informasi.update', $a) : route('bk.informasi.store') }}" x-data="{ judul: @js(old('judul', $a->judul)), isi: @js(old('isi', $a->isi)), kategori: @js(old('kategori', $a->kategori ?: 'karir')), cats: @js($cats) }" class="grid gap-6 xl:grid-cols-[1fr_380px]">
    @csrf @if ($a->exists) @method('PUT') @endif
    <div class="card card-pad space-y-4">
        <x-field name="judul" label="Judul"><input id="judul" name="judul" x-model="judul" class="input" required maxlength="150"></x-field>
        <x-field name="kategori" label="Kategori"><select id="kategori" name="kategori" x-model="kategori" class="select">@foreach ($cats as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
        <x-field name="isi" label="Isi" help="Gunakan baris kosong untuk memisahkan paragraf."><textarea id="isi" name="isi" x-model="isi" rows="14" class="textarea" required></textarea></x-field>
        <x-field name="jadwal" label="Jadwal terbit (opsional)"><input id="jadwal" name="jadwal" type="datetime-local" value="{{ old('jadwal', $a->published_at && $a->published_at->isFuture() ? $a->published_at->format('Y-m-d\TH:i') : '') }}" class="input max-w-xs"></x-field>
        <div class="flex flex-wrap gap-3"><button name="aksi" value="draf" class="btn btn-secondary">Simpan draf</button><button name="aksi" value="terbit" class="btn btn-primary">Terbitkan</button></div>
    </div>
    <aside>
        <p class="mb-2 text-xs font-bold tracking-wider text-muted uppercase">Pratinjau tampilan siswa</p>
        <div class="mx-auto w-full max-w-[340px] rounded-[28px] border-4 border-ink bg-bg p-4">
            <span class="chip chip-soft" x-text="cats[kategori]"></span>
            <h3 class="mt-2 text-xl leading-tight" x-text="judul || 'Judul informasi'"></h3>
            <div class="mt-3 text-sm leading-relaxed whitespace-pre-line" x-text="isi || 'Isi informasi akan tampil di sini.'"></div>
        </div>
    </aside>
</form>
@endsection
