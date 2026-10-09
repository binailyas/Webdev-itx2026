@extends('layouts.student')
@section('title', 'Buat laporan')
@section('heading', 'Buat laporan')
@section('back', route('siswa.beranda'))

@section('content')
@php $icons = ['Perundungan' => 'users', 'Kekerasan fisik' => 'alert-triangle', 'Kekerasan verbal' => 'message', 'Pelecehan' => 'shield-check', 'Ancaman' => 'alert-circle', 'Perundungan online' => 'wifi-off', 'Lainnya' => 'info']; @endphp
<form method="post" action="{{ route('siswa.laporan.store') }}" enctype="multipart/form-data" x-data="reportForm()" x-init="load()" @input.debounce.800ms="save()" novalidate>
    @csrf
    {{-- Stepper 4 titik --}}
    <div class="mb-5 flex items-center gap-2" role="progressbar" aria-valuemin="1" aria-valuemax="4" :aria-valuenow="step" aria-label="Langkah laporan">
        <template x-for="n in 4"><span class="h-2 flex-1 rounded-full" :class="n <= step ? 'bg-primary' : 'bg-soft'"></span></template>
    </div>
    <p class="mb-4 text-xs font-bold tracking-wider text-muted uppercase" x-text="'Langkah ' + step + ' dari 4'"></p>

    @if ($errors->any())<div class="mb-4 rounded-xl border-2 border-danger/40 bg-danger-soft p-3 text-sm font-semibold text-danger-dark">{{ $errors->first() }}</div>@endif

    {{-- 1. Kategori --}}
    <section x-show="step === 1">
        <h2 class="mb-4 text-2xl">Apa yang terjadi?</h2>
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
    </section>

    {{-- 2. Detail kejadian --}}
    <section x-show="step === 2" x-cloak class="space-y-4">
        <h2 class="text-2xl">Ceritakan kejadiannya</h2>
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
                    <button type="button" class="chip cursor-pointer" :class="f.lokasi === '{{ $l }}' ? 'border-primary bg-primary text-white' : 'chip-gray'" @click="f.lokasi = '{{ $l }}'; save()">{{ $l }}</button>
                @endforeach
            </div>
            <input id="lokasi" name="lokasi" x-model="f.lokasi" maxlength="80" class="input" placeholder="Atau tulis lokasi lain">
        </div>
        <x-field name="pihak_terlibat" label="Siapa saja yang terlibat? (opsional)" help="Boleh nama atau panggilan. Pisahkan dengan koma."><input id="pihak_terlibat" name="pihak_terlibat" x-model="f.pihak_terlibat" maxlength="300" class="input" placeholder="contoh: Dimas, Rafi"></x-field>
    </section>

    {{-- 3. Bukti dan prioritas --}}
    <section x-show="step === 3" x-cloak class="space-y-5">
        <div>
            <h2 class="mb-1 text-2xl">Punya bukti? <span class="text-base font-medium text-muted">(opsional)</span></h2>
            <label class="mt-3 flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed border-line bg-white px-4 py-8 text-center hover:bg-soft">
                <x-icon name="upload" :size="26" class="text-primary" /><span class="mt-2 text-sm font-bold">Tambah foto atau berkas</span><span class="text-xs text-muted">Maksimal 5 berkas, masing-masing 10 MB</span>
                <input type="file" name="lampiran[]" multiple accept="image/*,.pdf,.doc,.docx" class="sr-only" @change="files = [...$event.target.files].slice(0, 5)">
            </label>
            <ul class="mt-3 space-y-2"><template x-for="(fl, i) in files" :key="i"><li class="flex items-center gap-3 rounded-xl border-2 border-line bg-white p-3 text-sm"><x-icon name="file-text" :size="18" class="text-primary" /><span class="flex-1 truncate font-semibold" x-text="fl.name"></span><span class="text-xs text-muted" x-text="(fl.size/1048576).toFixed(1) + ' MB'"></span></li></template></ul>
        </div>
        <div>
            <h2 class="mb-3 text-2xl">Seberapa mendesak?</h2>
            <div class="space-y-3" role="radiogroup">
                @foreach ([['rendah', 'Rendah', 'Tidak mendesak, hanya ingin BK tahu.'], ['sedang', 'Sedang', 'Mengganggu, perlu ditindaklanjuti.'], ['tinggi', 'Tinggi', 'Berulang atau berdampak serius.'], ['darurat', 'Darurat', 'Ada bahaya sekarang.']] as [$v, $l, $d])
                    <label class="cursor-pointer"><input type="radio" name="prioritas" value="{{ $v }}" x-model="f.prioritas" class="peer sr-only">
                        <span class="card flex items-center gap-3 p-4 peer-checked:border-primary peer-checked:bg-soft"><x-priority-chip :priority="$v" /><span class="text-sm font-semibold">{{ $d }}</span></span></label>
                @endforeach
            </div>
            <div x-show="f.prioritas === 'darurat'" x-cloak class="mt-3 rounded-xl border-2 border-danger/40 bg-danger-soft p-4 text-sm font-semibold text-danger-dark">
                Jika kamu dalam bahaya sekarang, hubungi 112.
                <a href="{{ route('darurat') }}" class="btn btn-danger btn-sm mt-3 w-full">Buka bantuan darurat</a>
            </div>
        </div>
    </section>

    {{-- 4. Tinjau --}}
    <section x-show="step === 4" x-cloak class="space-y-4">
        <h2 class="text-2xl">Tinjau laporanmu</h2>
        <div class="card card-pad space-y-3 text-sm">
            @foreach ([['Kategori', 'catName()', 1], ['Judul', 'f.judul', 2], ['Cerita', "f.kronologi.slice(0, 160) + (f.kronologi.length > 160 ? '…' : '')", 2], ['Tanggal', "f.tanggal_kejadian || '—'", 2], ['Lokasi', "f.lokasi || '—'", 2], ['Lampiran', "files.length + ' berkas'", 3], ['Prioritas', "f.prioritas", 3]] as [$l, $expr, $goto])
                <div class="flex items-start justify-between gap-3 border-b-2 border-line/60 pb-3 last:border-0 last:pb-0">
                    <div class="min-w-0"><p class="text-xs font-bold tracking-wider text-muted uppercase">{{ $l }}</p><p class="font-semibold capitalize-first break-words" x-text="{!! $expr !!}"></p></div>
                    <button type="button" class="shrink-0 text-xs font-bold text-primary-dark" @click="step = {{ $goto }}">Ubah</button>
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
    <div class="mt-8 flex gap-3">
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
    return {
        step: {{ $errors->any() ? 4 : 1 }}, saved: false, files: [], err: {},
        f: { category_id: @js((string) old('category_id', '')), judul: @js(old('judul', '')), kronologi: @js(old('kronologi', '')), tanggal_kejadian: @js(old('tanggal_kejadian', '')), lokasi: @js(old('lokasi', '')), pihak_terlibat: @js(old('pihak_terlibat', '')), prioritas: @js(old('prioritas', 'sedang')) },
        catName() { return cats[this.f.category_id] || '—'; },
        next() {
            this.err = {};
            if (this.step === 2) {
                if (! this.f.judul.trim()) this.err.judul = true;
                if (this.f.kronologi.trim().length < 10) this.err.kronologi = true;
                if (Object.keys(this.err).length) return;
            }
            this.step++; window.scrollTo(0, 0);
        },
        save() { try { localStorage.setItem('bk-draf', JSON.stringify(this.f)); this.saved = true; setTimeout(() => this.saved = false, 1800); } catch (e) {} },
        load() { try { const d = JSON.parse(localStorage.getItem('bk-draf') || 'null'); if (d && ! this.f.judul && ! this.f.kronologi) this.f = { ...this.f, ...d }; } catch (e) {} },
        clear() { try { localStorage.removeItem('bk-draf'); } catch (e) {} },
    };
}
</script>
@endpush
@endsection
