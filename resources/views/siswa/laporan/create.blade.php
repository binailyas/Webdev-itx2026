@extends('layouts.student')
@section('title', 'Buat laporan')
@section('heading', 'Buat laporan')
@section('back', route('siswa.beranda'))

@section('content')
@php $icons = ['Perundungan' => 'users', 'Kekerasan fisik' => 'alert-triangle', 'Kekerasan verbal' => 'message', 'Pelecehan' => 'shield-check', 'Ancaman' => 'alert-circle', 'Perundungan online' => 'wifi-off', 'Lainnya' => 'info']; @endphp
<form method="post" action="{{ route('siswa.laporan.store') }}" enctype="multipart/form-data" x-data="reportForm()" x-init="load()" @input.debounce.800ms="save()" @keydown.escape.window="cancelEdit()" x-effect="document.body.style.overflow = editing ? 'hidden' : ''" novalidate>
    @csrf
    {{-- Stepper 4 titik --}}
    <div class="mb-5 flex items-center gap-2" role="progressbar" aria-valuemin="1" aria-valuemax="4" :aria-valuenow="step" aria-label="Langkah laporan">
        <template x-for="n in 4"><span class="h-2 flex-1 rounded-full" :class="n <= step ? 'bg-primary' : 'bg-soft'"></span></template>
    </div>
    <p class="mb-4 text-xs font-bold tracking-wider text-muted uppercase" x-text="'Langkah ' + step + ' dari 4'"></p>

    @if ($errors->any())<div class="mb-4 rounded-xl border-2 border-danger/40 bg-danger-soft p-3 text-sm font-semibold text-danger-dark">{{ $errors->first() }}</div>@endif

    {{-- 1. Kategori --}}
    <section x-show="step === 1 || editing === 1" :class="editing === 1 && 'edit-overlay'" aria-labelledby="h-1">
        <div class="mx-auto max-w-[640px]">
            <template x-if="editing === 1"><div class="mb-4 flex items-center justify-between gap-3"><span class="chip chip-soft">Ubah dari tinjauan</span><button type="button" class="btn btn-primary btn-sm" @click="done(1)">Simpan perubahan</button></div></template>
            <h2 id="h-1" class="mb-4 text-2xl">Apa yang terjadi?</h2>
            <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="Kategori">
                @foreach ($categories as $c)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="category_id" value="{{ $c->id }}" x-model="f.category_id" class="peer sr-only">
                        <span class="card flex min-h-28 flex-col items-start gap-3 p-4 peer-checked:border-primary peer-checked:bg-soft peer-focus-visible:outline-2 peer-focus-visible:outline-primary">
                            <span class="inline-flex size-10 items-center justify-center rounded-xl bg-soft text-primary"><x-icon :name="$icons[$c->name] ?? 'info'" :size="20" /></span>
                            <span class="text-sm font-bold">{{ $c->name }}</span>
                        </span>
                        <x-icon name="check-circle" :size="20" class="absolute top-3 right-3 hidden text-primary peer-checked:block" />
                    </label>
                @endforeach
            </div>
            <p x-show="err.category_id" x-cloak class="error-text">Pilih salah satu kategori.</p>
        </div>
    </section>

    {{-- 2. Detail kejadian --}}
    <section x-show="step === 2 || editing === 2" x-cloak :class="editing === 2 && 'edit-overlay'" class="space-y-4" aria-labelledby="h-2">
        <div class="mx-auto max-w-[640px] space-y-4">
            <template x-if="editing === 2"><div class="flex items-center justify-between gap-3"><span class="chip chip-soft">Ubah dari tinjauan</span><button type="button" class="btn btn-primary btn-sm" @click="done(2)">Simpan perubahan</button></div></template>
            <h2 id="h-2" class="text-2xl">Ceritakan kejadiannya</h2>
            <x-field name="judul" label="Judul singkat"><input id="judul" name="judul" x-model="f.judul" maxlength="150" class="input" placeholder="contoh: Diejek terus di kantin"><p x-show="err.judul" x-cloak class="error-text">Beri judul singkat.</p></x-field>
            <x-field name="kronologi" label="Ceritakan kejadiannya" help="Tulis sebanyak yang kamu nyaman bagikan.">
                <textarea id="kronologi" name="kronologi" x-model="f.kronologi" rows="6" maxlength="5000" class="textarea"></textarea>
                <div class="mt-1 flex justify-between text-xs"><span x-show="err.kronologi" x-cloak class="font-semibold text-danger-dark">Ceritakan sedikit supaya kami bisa membantu.</span><span class="ml-auto text-muted" x-text="f.kronologi.length + '/5000'"></span></div>
            </x-field>
            <x-field name="tanggal_kejadian" label="Tanggal kejadian"><input id="tanggal_kejadian" name="tanggal_kejadian" type="date" x-model="f.tanggal_kejadian" max="{{ now()->toDateString() }}" class="input"></x-field>
            <div>
                <label class="label" for="lokasi">Lokasi</label>
                <div class="mb-2 flex flex-wrap gap-2">
                    @foreach (['Kelas', 'Kantin', 'Toilet', 'Lapangan', 'Media sosial', 'Lainnya'] as $l)
                        <button type="button" class="chip" :class="f.lokasi === '{{ $l }}' ? 'border-primary bg-primary text-white' : 'chip-gray hover:bg-soft'" @click="f.lokasi = '{{ $l }}'; save()">{{ $l }}</button>
                    @endforeach
                </div>
                <input id="lokasi" name="lokasi" x-model="f.lokasi" maxlength="80" class="input" placeholder="Atau tulis lokasi lain">
            </div>

            {{-- S4 (v1.1): peran tiap orang yang terlibat --}}
            <fieldset class="space-y-3 rounded-xl border-2 border-line bg-white p-4">
                <legend class="px-1 text-[13px] font-bold">Siapa saja yang terlibat? (opsional)</legend>
                <p class="help !mt-0">Pilih peran tiap orang. Boleh nama atau panggilan. Peran ini hanya masukan awal; guru BK dan wali kelas yang memastikan.</p>
                <template x-for="(p, i) in f.pihak" :key="i">
                    <div class="flex flex-wrap items-center gap-2 rounded-xl border-2 border-line bg-bg p-2">
                        <input :name="'pihak[' + i + '][nama]'" x-model="p.nama" :readonly="p.self" maxlength="80" class="input !h-11 min-w-32 flex-1" :class="p.self && 'bg-soft font-bold'" placeholder="Nama atau panggilan" :aria-label="'Nama orang ke-' + (i + 1)">
                        <select :name="'pihak[' + i + '][peran]'" x-model="p.peran" class="select !h-11 !w-auto" :aria-label="'Peran orang ke-' + (i + 1)">
                            <option value="korban">Korban</option><option value="terlapor">Terduga pelaku</option><option value="saksi">Saksi</option><option value="lainnya">Lainnya</option>
                        </select>
                        <input type="hidden" :name="'pihak[' + i + '][self]'" :value="p.self ? 1 : 0">
                        <button type="button" class="btn-icon bg-gray-soft !text-muted" @click="f.pihak.splice(i, 1); save()" aria-label="Hapus orang ini"><x-icon name="x" :size="16" /></button>
                    </div>
                </template>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="chip chip-soft hover:bg-line/50" @click="f.pihak.push({ nama: '', peran: 'terlapor', self: false }); save()"><x-icon name="plus" :size="12" />Tambah orang</button>
                    <button type="button" class="chip chip-mint hover:brightness-95" x-show="! f.pihak.some(p => p.self)" @click="f.pihak.unshift({ nama: 'Saya sendiri', peran: 'korban', self: true }); save()"><x-icon name="user" :size="12" />Saya sendiri (korban)</button>
                </div>
            </fieldset>
        </div>
    </section>

    {{-- 3. Bukti dan prioritas (S5: siswa memilih prioritas; AI hanya memberi saran) --}}
    <section x-show="step === 3 || editing === 3" x-cloak :class="editing === 3 && 'edit-overlay'" aria-labelledby="h-3">
        <div class="mx-auto max-w-[640px] space-y-5">
            <template x-if="editing === 3"><div class="flex items-center justify-between gap-3"><span class="chip chip-soft">Ubah dari tinjauan</span><button type="button" class="btn btn-primary btn-sm" @click="done(3)">Simpan perubahan</button></div></template>
            <div>
                <h2 id="h-3" class="mb-1 text-2xl">Punya bukti? <span class="text-base font-medium text-muted">(opsional)</span></h2>
                <label class="mt-3 flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-line bg-white px-4 py-8 text-center hover:bg-soft">
                    <x-icon name="upload" :size="26" class="text-primary" /><span class="mt-2 text-sm font-bold">Tambah foto atau berkas</span><span class="text-xs text-muted">Maksimal 5 berkas, masing-masing 10 MB</span>
                    <input type="file" name="lampiran[]" multiple accept="image/*,.pdf,.doc,.docx" class="sr-only" @change="files = [...$event.target.files].slice(0, 5)">
                </label>
                <ul class="mt-3 space-y-2"><template x-for="(fl, i) in files" :key="i"><li class="flex items-center gap-3 rounded-xl border-2 border-line bg-white p-3 text-sm"><x-icon name="file-text" :size="18" class="text-primary" /><span class="flex-1 truncate font-semibold" x-text="fl.name"></span><span class="text-xs text-muted" x-text="(fl.size/1048576).toFixed(1) + ' MB'"></span></li></template></ul>
            </div>

            <div>
                <h3 class="mb-1 text-xl">Seberapa mendesak?</h3>
                <p class="mb-3 text-xs text-muted">Pilihanmu jadi masukan awal. Sistem juga memberi saran, lalu wali kelas dan guru BK yang memastikan.</p>
                <div class="space-y-3" role="radiogroup" aria-label="Prioritas">
                    @foreach ([['rendah', 'Tidak mendesak, hanya ingin BK tahu.'], ['sedang', 'Mengganggu, perlu ditindaklanjuti.'], ['tinggi', 'Berulang atau berdampak serius.']] as [$v, $d])
                        <label class="cursor-pointer"><input type="radio" name="prioritas" value="{{ $v }}" x-model="f.prioritas" class="peer sr-only">
                            <span class="card flex items-center gap-3 p-4 peer-checked:border-primary peer-checked:bg-soft peer-focus-visible:outline-2 peer-focus-visible:outline-primary"><x-priority-chip :priority="$v" /><span class="text-sm font-semibold">{{ $d }}</span></span></label>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border-2 border-danger/40 bg-danger-soft p-4 text-sm font-semibold text-danger-dark">
                Jika kamu dalam bahaya sekarang, hubungi 112.
                <a href="{{ route('darurat') }}" class="btn btn-danger btn-sm mt-3 w-full">Buka bantuan darurat</a>
            </div>
        </div>
    </section>

    {{-- 4. Tinjau: tombol Ubah membuka lapisan edit; posisi halaman dan isian tidak berubah (S2) --}}
    <section x-show="step === 4" x-cloak class="space-y-4">
        <h2 class="text-2xl">Tinjau laporanmu</h2>
        <div class="card card-pad space-y-3 text-sm">
            @foreach ([['Kategori', 'catName()', 1], ['Judul', 'f.judul', 2], ['Cerita', "f.kronologi.slice(0, 160) + (f.kronologi.length > 160 ? '…' : '')", 2], ['Tanggal', "f.tanggal_kejadian || '—'", 2], ['Lokasi', "f.lokasi || '—'", 2], ['Pihak terlibat', "pihakText()", 2], ['Lampiran', "files.length + ' berkas'", 3], ['Prioritas', "prioText()", 3]] as [$l, $expr, $goto])
                <div class="flex items-start justify-between gap-3 border-b-2 border-line/60 pb-3 last:border-0 last:pb-0">
                    <div class="min-w-0"><p class="text-xs font-bold tracking-wider text-muted uppercase">{{ $l }}</p><p class="font-semibold break-words" x-text="{!! $expr !!}"></p></div>
                    <button type="button" class="shrink-0 rounded-lg px-2 py-1 text-xs font-bold text-primary-dark hover:bg-soft" @click="edit({{ $goto }})" aria-label="Ubah {{ strtolower($l) }}">Ubah</button>
                </div>
            @endforeach
        </div>
        <div class="rounded-xl border-2 border-line bg-soft p-4 text-sm font-semibold text-primary-dark">
            <x-icon name="lock" :size="16" class="mr-1 inline" />
            @if (request()->attributes->get('is_anon')) Laporan ini dikirim tanpa nama. Identitasmu tidak terhubung ke laporan ini.
            @else BK dan Wali Kelas kamu dapat melihat namamu. Kamu bisa memilih lapor anonim lewat menu masuk.@endif
        </div>
    </section>

    {{-- Navigasi --}}
    <div class="mt-8 flex gap-3" x-show="! editing">
        <button type="button" class="btn btn-secondary" x-show="step > 1" @click="step--; window.scrollTo(0, 0)">Kembali</button>
        <button type="button" class="btn btn-primary flex-1" x-show="step < 4" :disabled="step === 1 && ! f.category_id" @click="next()">Lanjut</button>
        <button type="submit" class="btn btn-primary flex-1" x-show="step === 4" x-cloak @click="clear()">Kirim laporan</button>
    </div>
    <p x-show="saved" x-cloak x-transition.opacity class="mt-3 text-center text-xs font-semibold text-accent-text" role="status">Draf tersimpan</p>
</form>

@push('scripts')
<script>
function reportForm() {
    const cats = @js($categories->pluck('name', 'id'));
    const labels = { korban: 'korban', terlapor: 'terduga pelaku', saksi: 'saksi', lainnya: 'lainnya' };
    const prio = { rendah: 'Rendah', sedang: 'Sedang', tinggi: 'Tinggi' };
    return {
        step: {{ $errors->any() ? 4 : 1 }}, editing: null, saved: false, files: [], err: {},
        f: { category_id: @js((string) old('category_id', '')), judul: @js(old('judul', '')), kronologi: @js(old('kronologi', '')), tanggal_kejadian: @js(old('tanggal_kejadian', '')), lokasi: @js(old('lokasi', '')), prioritas: @js(old('prioritas', 'rendah')), pihak: @js(array_values(old('pihak', []))) },
        catName() { return cats[this.f.category_id] || '—'; },
        prioText() { return prio[this.f.prioritas] || '—'; },
        pihakText() { const p = this.f.pihak.filter(x => (x.nama || '').trim()); return p.length ? p.map(x => x.nama + ' (' + (labels[x.peran] || x.peran) + ')').join(', ') : '—'; },
        validate(n) {
            this.err = {};
            if (n === 1 && ! this.f.category_id) this.err.category_id = true;
            if (n === 2) {
                if (! this.f.judul.trim()) this.err.judul = true;
                if (this.f.kronologi.trim().length < 10) this.err.kronologi = true;
            }
            return Object.keys(this.err).length === 0;
        },
        next() { if (! this.validate(this.step)) return; this.step++; window.scrollTo(0, 0); },
        // S2: buka/tutup lapisan edit tanpa mengubah langkah (halaman tinjauan tetap di tempatnya).
        edit(n) { this.err = {}; this.editing = n; },
        done(n) { if (! this.validate(n)) return; this.editing = null; this.save(); },
        cancelEdit() { if (this.editing) { this.editing = null; } },
        save() { try { localStorage.setItem('bk-draf', JSON.stringify(this.f)); this.saved = true; setTimeout(() => this.saved = false, 1800); } catch (e) {} },
        load() { try { const d = JSON.parse(localStorage.getItem('bk-draf') || 'null'); if (d && ! this.f.judul && ! this.f.kronologi) { this.f = { ...this.f, ...d, prioritas: d.prioritas || 'rendah', pihak: Array.isArray(d.pihak) ? d.pihak : [] }; } } catch (e) {} },
        clear() { try { localStorage.removeItem('bk-draf'); } catch (e) {} },
    };
}
</script>
@endpush
@endsection
