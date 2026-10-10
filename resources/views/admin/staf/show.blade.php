@extends('layouts.staff')
@section('title', $u->name)
@section('heading', 'Detail akun ' . ($u->hasRole('bk') ? 'guru BK' : 'wali kelas'))

@section('actions')
    <x-btn variant="secondary" :href="route('admin.staf.index', ['tab' => $u->hasRole('bk') ? 'bk' : 'wali'])" icon="chevron-left">Kembali</x-btn>
@endsection

@section('content')
<div x-data="{ tab: 'info', edit: {{ $errors->any() ? 'true' : 'false' }} }">
    <section class="card card-pad flex flex-wrap items-center gap-5">
        <x-avatar :name="$u->name" :size="72" class="!text-xl" />
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl">{{ $u->name }}</h2>
            <p class="text-sm text-muted">{{ $u->email }} · {{ $u->role->label }}</p>
            @if ($u->hasRole('wali_kelas'))
                <p class="mt-2">@forelse ($u->classrooms as $c)<span class="chip chip-soft mr-1">{{ $c->nama_kelas }}</span>@empty<span class="chip chip-warn"><x-icon name="alert-triangle" :size="14" />Kelas belum ditetapkan</span>@endforelse</p>
            @endif
        </div>
        @if ($u->is_active)<span class="chip chip-mint"><x-icon name="check-circle" :size="14" />Aktif</span>@else<span class="chip chip-gray">Nonaktif</span>@endif
    </section>

    <div class="tabs mt-6" role="tablist">
        @foreach (['info' => 'Info', 'login' => 'Aktivitas login', 'aksi' => 'Tindakan'] as $k => $l)
            <button type="button" role="tab" class="tab" :class="tab === '{{ $k }}' && 'is-active'" @click="tab = '{{ $k }}'">{{ $l }}</button>
        @endforeach
    </div>

    <section x-show="tab === 'info'" class="mt-4 space-y-4">
        @if ($u->hasRole('wali_kelas'))
            <div class="card card-pad">
                <div class="mb-4 flex items-center justify-between"><h3 class="text-lg">Kelas asuhan</h3><button type="button" class="btn btn-outline btn-sm" @click="edit = true; tab = 'aksi'">Ubah kelas asuhan</button></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($u->classrooms as $c)
                        <div class="rounded-xl border-2 border-line p-4"><span class="chip chip-soft">{{ $c->nama_kelas }}</span><p class="mt-2 text-sm font-semibold">{{ $c->students->count() }} siswa</p><p class="text-xs text-muted">Tahun ajaran {{ $c->tahun_ajaran }}</p></div>
                    @empty<p class="text-sm text-muted">Belum ada kelas asuhan.</p>@endforelse
                </div>
            </div>
        @endif
        <div class="card card-pad">
            <dl class="grid gap-4 sm:grid-cols-3">
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Laporan aktif {{ $u->hasRole('bk') ? 'dipegang' : 'terkait kelas' }}</dt><dd class="text-2xl font-bold">{{ $load }}</dd></div>
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">2FA</dt><dd class="font-semibold">{{ $u->two_factor_enabled ? 'Aktif' : 'Belum aktif' }}</dd></div>
                <div><dt class="text-xs font-bold tracking-wider text-muted uppercase">Login terakhir</dt><dd class="font-semibold">{{ $u->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</dd></div>
            </dl>
            <p class="mt-4 flex items-center gap-2 text-xs font-semibold text-muted"><x-icon name="lock" :size="14" />Hanya jumlah laporan yang tampil. Isi laporan tidak dapat dibuka admin.</p>
        </div>
    </section>

    <section x-show="tab === 'login'" x-cloak class="card mt-4 overflow-hidden">
        <table class="tbl"><thead><tr><th>Tanggal</th><th>Jenis</th></tr></thead><tbody>
            @forelse ($logins as $l)<tr><td>{{ $l->created_at->translatedFormat('d M Y, H:i') }}</td><td><span class="chip chip-gray">Login berhasil</span></td></tr>
            @empty<tr><td colspan="2"><x-empty title="Belum ada riwayat login" /></td></tr>@endforelse
        </tbody></table>
    </section>

    <section x-show="tab === 'aksi'" x-cloak class="mt-4 space-y-4">
        <form method="post" action="{{ route('admin.staf.update', $u) }}" class="card card-pad space-y-4">
            @csrf @method('PUT')
            <h3 class="text-lg">Ubah data akun</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Nama lengkap"><input id="name" name="name" value="{{ old('name', $u->name) }}" class="input" required></x-field>
                <x-field name="email" label="Email"><input id="email" name="email" type="email" value="{{ old('email', $u->email) }}" class="input" required></x-field>
            </div>
            @if ($u->hasRole('wali_kelas'))
                <div>
                    <p class="label">Kelas asuhan <span class="text-danger">*</span> <span class="font-medium text-muted">(minimal satu)</span></p>
                    <div class="flex flex-wrap gap-2 rounded-xl border-2 p-3 {{ $errors->has('classroom_ids') ? 'border-danger' : 'border-line' }}">
                        @foreach ($classrooms as $c)
                            <label class="cursor-pointer"><input type="checkbox" name="classroom_ids[]" value="{{ $c->id }}" class="peer sr-only" @checked(in_array($c->id, old('classroom_ids', $u->classroomIds())))>
                                <span class="chip chip-gray peer-checked:border-primary peer-checked:bg-soft peer-checked:text-primary-dark">{{ $c->nama_kelas }}</span></label>
                        @endforeach
                    </div>
                    @error('classroom_ids')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            @endif
            <label class="flex cursor-pointer items-center gap-3 text-sm font-bold"><input type="hidden" name="two_factor_enabled" value="0"><input type="checkbox" name="two_factor_enabled" value="1" class="check" @checked($u->two_factor_enabled)> Wajib verifikasi dua langkah (2FA)</label>
            <button class="btn btn-primary">Simpan perubahan</button>
        </form>

        <div class="card card-pad">
            <h3 class="mb-4 text-lg">Tindakan akun</h3>
            <div class="flex flex-wrap gap-3">
                <form method="post" action="{{ route('admin.staf.reset', $u) }}">@csrf<button class="btn btn-secondary"><x-icon name="key" :size="18" />Reset kata sandi</button></form>
                <form method="post" action="{{ route('admin.staf.toggle', $u) }}">@csrf<button class="btn btn-outline">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                @if ($load > 0)
                    <form method="post" action="{{ route('admin.staf.destroy', $u) }}" class="flex w-full flex-wrap items-end gap-3 rounded-xl border-2 border-danger/40 bg-danger-soft p-4"
                          data-confirm="Alihkan {{ $load }} laporan lalu hapus akun ini? Tindakan ini dicatat di audit log." data-confirm-title="Alihkan lalu hapus akun?" data-confirm-label="Alihkan lalu hapus" data-confirm-tone="danger">
                        @csrf @method('DELETE')
                        <div class="min-w-56 flex-1">
                            <label class="label" for="alihkan_ke">Alihkan ke petugas</label>
                            <select id="alihkan_ke" name="alihkan_ke" class="select" required>
                                <option value="">Pilih petugas</option>
                                @foreach ($others as $o)<option value="{{ $o->id }}">{{ $o->name }}</option>@endforeach
                            </select>
                            @error('alihkan_ke')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <button class="btn btn-danger"><x-icon name="trash" :size="18" />Alihkan lalu hapus</button>
                    </form>
                @else
                    <button type="button" class="btn btn-danger" @click="$dispatch('confirm', { action: '{{ route('admin.staf.destroy', $u) }}', method: 'DELETE', title: 'Hapus akun {{ $u->name }}?', text: 'Akun akan dihapus permanen.', button: 'Hapus akun', name: @js($u->name) })"><x-icon name="trash" :size="18" />Hapus akun</button>
                @endif
            </div>
            @if ($load > 0)<p class="mt-3 rounded-xl border-2 border-line bg-danger-soft p-3 text-sm font-semibold text-danger-dark">Akun ini memegang {{ $load }} laporan aktif. Alihkan dulu ke petugas lain sebelum menghapus. Isi laporan tidak ditampilkan.</p>@endif
        </div>
    </section>
</div>
@endsection
