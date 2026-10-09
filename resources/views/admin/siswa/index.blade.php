@extends('layouts.staff')
@section('title', 'Siswa')
@section('eyebrow', 'Direktori kesiswaan · Tahun ajaran ' . setting('tahun_ajaran'))
@section('heading', 'Manajemen akun siswa')

@section('actions')
    <span class="chip chip-gray"><span class="size-2 rounded-full bg-primary"></span>{{ fmt_num($totalAll) }} siswa terdaftar</span>
    <span class="chip chip-mint"><x-icon name="check-circle" :size="14" />{{ fmt_num($totalActive) }} aktif</span>
@endsection

@section('content')
<div x-data="studentPage()" @keydown.escape.window="panel = false">
    {{-- Toolbar --}}
    <form method="get" class="card card-pad flex flex-wrap items-center gap-3 !py-4">
        <label class="relative min-w-56 flex-1">
            <span class="sr-only">Cari siswa</span>
            <x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama siswa, NIS, atau email" class="input pl-11">
        </label>
        <select name="kelas" class="select !w-auto" onchange="this.form.submit()" aria-label="Kelas">
            <option value="">Semua kelas</option>
            @foreach ($classrooms as $c)<option value="{{ $c->id }}" @selected(request('kelas') == $c->id)>{{ $c->nama_kelas }}</option>@endforeach
        </select>
        <select name="status" class="select !w-auto" onchange="this.form.submit()" aria-label="Status">
            <option value="">Status: semua</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>
        <select name="angkatan" class="select !w-auto" onchange="this.form.submit()" aria-label="Angkatan">
            <option value="">Angkatan: semua</option>
            @foreach ($angkatan as $a)<option @selected(request('angkatan') == $a)>{{ $a }}</option>@endforeach
        </select>
        <x-btn variant="outline" :href="route('admin.impor.index')" icon="upload">Impor</x-btn>
        <x-btn variant="primary" icon="plus" @click="openNew()">Tambah siswa</x-btn>
    </form>

    {{-- Aksi massal --}}
    <form method="post" action="{{ route('admin.siswa.bulk') }}" x-show="sel.length" x-cloak
          class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border-2 border-b-4 border-line bg-soft p-3">
        @csrf
        <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="chip border-primary bg-primary text-white" x-text="sel.length + ' siswa terpilih'"></span>
        <span class="hidden text-sm text-muted md:inline">Terapkan instruksi serentak pada akun yang dipilih:</span>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <select name="classroom_id" class="select !h-9 !w-auto text-xs" aria-label="Kelas tujuan">
                <option value="">Kelas tujuan…</option>
                @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->nama_kelas }}</option>@endforeach
            </select>
            <button name="aksi" value="pindah" class="btn btn-secondary btn-sm"><x-icon name="refresh" :size="14" />Pindah kelas</button>
            <button name="aksi" value="reset" class="btn btn-secondary btn-sm"><x-icon name="key" :size="14" />Reset kata sandi</button>
            <button name="aksi" value="nonaktifkan" class="btn btn-danger btn-sm"><x-icon name="eye-off" :size="14" />Nonaktifkan</button>
            <button type="button" class="btn-icon bg-transparent !text-muted" @click="sel = []" aria-label="Batalkan pilihan"><x-icon name="x" :size="18" /></button>
        </div>
    </form>

    {{-- Tabel --}}
    <section class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr>
                    <th class="w-12"><input type="checkbox" class="check" aria-label="Pilih semua" @change="toggleAll($event)"></th>
                    <th>Siswa</th><th>NIS</th><th>Kelas</th><th>Status akun</th><th>Login terakhir</th><th class="text-right">Aksi</th>
                </tr></thead>
                <tbody>
                @forelse ($students as $s)
                    @php $p = $s->studentProfile; @endphp
                    <tr>
                        <td><input type="checkbox" class="check" value="{{ $s->id }}" x-model.number="sel" aria-label="Pilih {{ $s->name }}"></td>
                        <td>
                            <a href="{{ route('admin.siswa.show', $s) }}" class="flex items-center gap-3">
                                <x-avatar :name="$s->name" :tone="$s->is_active ? 'soft' : 'gray'" :size="40" />
                                <span class="min-w-0"><span class="block font-bold">{{ $s->name }}</span><span class="block truncate text-xs text-muted">{{ $s->email }}</span></span>
                            </a>
                        </td>
                        <td class="font-mono text-[13px]">{{ $p?->nis }}</td>
                        <td>@if ($p?->classroom)<span class="chip chip-gray">{{ $p->classroom->nama_kelas }}</span>@else<span class="text-muted">—</span>@endif</td>
                        <td>@if ($s->is_active)<span class="chip chip-mint"><span class="size-1.5 rounded-full bg-accent"></span>Aktif</span>@else<span class="chip chip-gray"><span class="size-1.5 rounded-full bg-muted"></span>Nonaktif</span>@endif</td>
                        <td class="whitespace-nowrap">{!! $s->last_login_at ? e($s->last_login_at->translatedFormat('d M, H:i')) : '<span class="italic text-muted">Belum pernah</span>' !!}</td>
                        <td class="text-right whitespace-nowrap">
                            <button type="button" class="btn btn-secondary btn-sm" @click="openEdit(@js(['id' => $s->id, 'name' => $s->name, 'email' => $s->email, 'nis' => $p?->nis, 'classroom_id' => $p?->classroom_id, 'angkatan' => $p?->angkatan, 'is_active' => $s->is_active]))">Ubah</button>
                            <a href="{{ route('admin.siswa.show', $s) }}" class="btn btn-secondary btn-sm">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="users" title="Belum ada akun" text="Tambahkan siswa pertama atau impor dari berkas." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </section>

    <p class="mt-4 flex items-center gap-2 rounded-xl border-2 border-line bg-white px-4 py-3 text-xs font-semibold text-muted"><x-icon name="shield-check" :size="16" class="text-accent-text" />Admin tidak memiliki akses ke isi laporan, chat, maupun skor siswa.</p>

    {{-- Panel samping 480px (A04) --}}
    <div x-show="panel" x-cloak class="fixed inset-0 z-40 bg-ink/40" @click="panel = false"></div>
    <aside x-show="panel" x-cloak x-transition:enter="transition duration-150" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           class="fixed inset-y-0 right-0 z-50 flex w-full max-w-[480px] flex-col border-l-2 border-line bg-white" role="dialog" aria-modal="true" aria-label="Formulir siswa">
        <div class="flex items-center justify-between border-b-2 border-line p-5">
            <h2 class="text-xl" x-text="f.id ? 'Ubah siswa' : 'Tambah siswa'"></h2>
            <button type="button" class="btn-icon bg-soft !text-primary-dark" @click="panel = false" aria-label="Tutup"><x-icon name="x" :size="18" /></button>
        </div>
        <form method="post" :action="f.id ? '{{ url('admin/siswa') }}/' + f.id : '{{ route('admin.siswa.store') }}'" class="flex flex-1 flex-col overflow-y-auto">
            @csrf
            <template x-if="f.id"><input type="hidden" name="_method" value="PUT"></template>
            <div class="flex-1 space-y-4 p-5">
                @if ($errors->any())<div class="rounded-xl border-2 border-danger/40 bg-danger-soft p-3 text-sm font-semibold text-danger-dark">{{ $errors->first() }}</div>@endif
                <x-field name="name" label="Nama lengkap"><input id="name" name="name" x-model="f.name" class="input" required></x-field>
                <x-field name="nis" label="NIS"><input id="nis" name="nis" x-model="f.nis" class="input font-mono" required></x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field name="classroom_id" label="Kelas">
                        <select id="classroom_id" name="classroom_id" x-model="f.classroom_id" class="select" required>
                            <option value="">Pilih kelas</option>
                            @foreach ($classrooms as $c)<option value="{{ $c->id }}">{{ $c->nama_kelas }}</option>@endforeach
                        </select>
                    </x-field>
                    <x-field name="angkatan" label="Angkatan"><input id="angkatan" name="angkatan" x-model="f.angkatan" class="input" placeholder="2024"></x-field>
                </div>
                <x-field name="email" label="Email atau kontak (opsional)"><input id="email" name="email" type="email" x-model="f.email" class="input"></x-field>
                <template x-if="! f.id">
                    <x-field name="password" label="Kata sandi awal" help="Dibuat otomatis. Tampil sekali setelah disimpan.">
                        <div class="flex gap-2"><input id="password" name="password" x-model="f.password" class="input font-mono" required><button type="button" class="btn btn-secondary" @click="f.password = gen()">Buat ulang</button></div>
                    </x-field>
                </template>
                <template x-if="f.id">
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border-2 border-line p-4">
                        <span><span class="block text-sm font-bold">Status akun</span><span class="text-xs text-muted">Aktif dapat masuk ke aplikasi</span></span>
                        <input type="checkbox" name="is_active" value="1" x-model="f.is_active" class="peer sr-only">
                        <span class="relative h-7 w-12 rounded-full bg-line transition-colors peer-checked:bg-accent after:absolute after:top-0.5 after:left-0.5 after:size-6 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-5"></span>
                    </label>
                </template>
            </div>
            <div class="flex gap-3 border-t-2 border-line p-5">
                <button class="btn btn-primary flex-1">Simpan</button>
                <button type="button" class="btn btn-secondary" @click="panel = false">Batal</button>
            </div>
        </form>
    </aside>
</div>

@push('scripts')
<script>
function studentPage() {
    const blank = () => ({ id: null, name: '', nis: '', classroom_id: '', angkatan: '', email: '', is_active: true, password: gen() });
    function gen() { const w = ['kunci','pagi','senja','bintang','hujan','laut','angin','bulan']; return w[Math.floor(Math.random()*w.length)] + '-' + w[Math.floor(Math.random()*w.length)] + '-' + (10 + Math.floor(Math.random()*90)); }
    return {
        sel: [], panel: {{ $errors->any() ? 'true' : 'false' }}, f: blank(), gen,
        openNew() { this.f = blank(); this.panel = true; },
        openEdit(row) { this.f = { ...row, email: row.email && !row.email.endsWith('@siswa.local') ? row.email : '', password: '' }; this.panel = true; },
        toggleAll(e) { this.sel = e.target.checked ? @js($students->pluck('id')) : []; },
    };
}
</script>
@endpush
@endsection
