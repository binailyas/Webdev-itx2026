@extends('layouts.staff')
@section('title', $a->exists ? 'Ubah informasi' : 'Tulis informasi')
@section('heading', $a->exists ? 'Ubah informasi' : 'Tulis informasi')
@section('actions')<x-btn variant="secondary" :href="route('bk.informasi.index')" icon="chevron-left">Kembali</x-btn>@endsection

@section('content')
<form method="post" action="{{ $a->exists ? route('bk.informasi.update', $a) : route('bk.informasi.store') }}" enctype="multipart/form-data"
      x-data="{ judul: @js(old('judul', $a->judul)), isi: @js(old('isi', $a->isi)), kategori: @js(old('kategori', $a->kategori ?: 'karir')), cats: @js($cats), img: @js($a->image_url), hapus: false,
                pick(e) { const f = e.target.files[0]; this.hapus = false; this.img = f ? URL.createObjectURL(f) : @js($a->image_url); } }"
      class="grid gap-6 xl:grid-cols-[1fr_380px]">
    @csrf @if ($a->exists) @method('PUT') @endif
    <div class="card card-pad space-y-4">
        <x-field name="judul" label="Judul"><input id="judul" name="judul" x-model="judul" class="input" required maxlength="150"></x-field>
        <x-field name="kategori" label="Kategori"><select id="kategori" name="kategori" x-model="kategori" class="select">@foreach ($cats as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>

        {{-- B2: unggah gambar --}}
        <x-field name="gambar" label="Gambar (opsional)" help="JPG, PNG, atau WebP, maksimal 2 MB.">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-dashed border-line bg-bg p-3 hover:bg-soft">
                <span class="inline-flex size-10 items-center justify-center rounded-lg bg-soft text-primary"><x-icon name="upload" :size="18" /></span>
                <span class="text-sm font-semibold">Pilih gambar…</span>
                <input id="gambar" type="file" name="gambar" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
            </label>
            <template x-if="img && ! hapus"><div class="mt-3 flex items-center gap-3"><img :src="img" alt="Pratinjau gambar" class="h-24 rounded-xl border-2 border-line object-cover">
                @if ($a->image_path)<label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-danger-dark"><input type="checkbox" name="hapus_gambar" value="1" class="check !size-4" x-model="hapus">Hapus gambar</label>@endif</div></template>
        </x-field>

        <x-field name="isi" label="Isi" help="Gunakan baris kosong untuk memisahkan paragraf."><textarea id="isi" name="isi" x-model="isi" rows="12" class="textarea" required></textarea></x-field>
        <x-field name="jadwal" label="Jadwal terbit (opsional)"><input id="jadwal" name="jadwal" type="datetime-local" value="{{ old('jadwal', $a->published_at && $a->published_at->isFuture() ? $a->published_at->format('Y-m-d\TH:i') : '') }}" class="input max-w-xs"></x-field>
        <div class="flex flex-wrap gap-3"><button name="aksi" value="draf" class="btn btn-secondary">Simpan draf</button><button name="aksi" value="terbit" class="btn btn-primary">Terbitkan</button></div>
    </div>
    <aside>
        <p class="mb-2 text-xs font-bold tracking-wider text-muted uppercase">Pratinjau tampilan siswa</p>
        <div class="mx-auto w-full max-w-[340px] rounded-[28px] border-4 border-ink bg-bg p-4">
            <template x-if="img && ! hapus"><img :src="img" alt="" class="mb-3 h-36 w-full rounded-xl border-2 border-line object-cover"></template>
            <span class="chip chip-soft" x-text="cats[kategori]"></span>
            <h3 class="mt-2 text-xl leading-tight" x-text="judul || 'Judul informasi'"></h3>
            <div class="mt-3 text-sm leading-relaxed whitespace-pre-line" x-text="isi || 'Isi informasi akan tampil di sini.'"></div>
        </div>
    </aside>
</form>
@endsection
