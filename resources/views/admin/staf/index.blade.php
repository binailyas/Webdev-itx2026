@extends('layouts.staff')
@section('title', 'BK dan Wali Kelas')
@section('heading', 'Akun BK dan Wali Kelas')
@section('subheading', 'Buat akun staf dan tetapkan kelas asuhan untuk Wali Kelas.')

@section('actions')
    <x-btn variant="primary" icon="plus" @click="$dispatch('open-staff')">Tambah akun</x-btn>
@endsection

@section('content')
<div x-data="{ modal: {{ $errors->any() ? 'true' : 'false' }}, role: '{{ old('role', $tab === 'wali' ? 'wali_kelas' : 'bk') }}', picked: @js(array_map('intval', old('classroom_ids', []))) }" @open-staff.window="modal = true">
    <div class="tabs" role="tablist">
        <a href="{{ route('admin.staf.index', ['tab' => 'bk']) }}" class="tab" @if ($tab === 'bk') aria-current="page" @endif>Guru BK <span class="chip chip-soft ml-1 h-5">{{ $counts['bk'] }}</span></a>
        <a href="{{ route('admin.staf.index', ['tab' => 'wali']) }}" class="tab" @if ($tab === 'wali') aria-current="page" @endif>Wali Kelas <span class="chip chip-soft ml-1 h-5">{{ $counts['wali'] }}</span></a>
    </div>

    <section class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr>
                    <th>Nama</th><th>Email</th>
                    @if ($tab === 'wali')<th>Kelas asuhan</th>@endif
                    <th>2FA</th><th>{{ $tab === 'wali' ? 'Laporan aktif terkait kelas' : 'Laporan aktif dipegang' }}</th><th>Status</th><th class="text-right">Aksi</th>
                </tr></thead>
                <tbody>
                @forelse ($staff as $s)
                    <tr>
                        <td><a href="{{ route('admin.staf.show', $s) }}" class="flex items-center gap-3 font-bold"><x-avatar :name="$s->name" :size="36" />{{ $s->name }}</a></td>
                        <td class="text-muted">{{ $s->email }}</td>
                        @if ($tab === 'wali')
                            <td>
                                @forelse ($s->classrooms as $c)<span class="chip chip-soft mr-1">{{ $c->nama_kelas }}</span>
                                @empty<span class="chip chip-warn"><x-icon name="alert-triangle" :size="14" />Kelas belum ditetapkan</span>@endforelse
                            </td>
                        @endif
                        <td>@if ($s->two_factor_enabled)<span class="chip chip-mint"><x-icon name="check" :size="14" />Aktif</span>@else<span class="chip chip-gray">Belum</span>@endif</td>
                        <td class="font-bold">{{ $s->load }}</td>
                        <td>@if ($s->is_active)<span class="chip chip-mint">Aktif</span>@else<span class="chip chip-gray">Nonaktif</span>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.staf.show', $s) }}" class="btn btn-secondary btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty icon="users" title="Belum ada akun" text="Tambahkan akun pertama dengan tombol Tambah akun." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Modal buat akun (A08) --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-ink/50 p-4" role="dialog" aria-modal="true" @keydown.escape.window="modal = false">
        <form method="post" action="{{ route('admin.staf.store') }}" @click.outside="modal = false" class="w-full max-w-[560px] rounded-xl border-2 border-b-4 border-line bg-white p-6">
            @csrf
            <div class="mb-5 flex items-center justify-between"><h2 class="text-xl">Buat akun staf</h2><button type="button" class="btn-icon bg-soft !text-primary-dark" @click="modal = false" aria-label="Tutup"><x-icon name="x" :size="18" /></button></div>
            <div class="space-y-4">
                <x-field name="name" label="Nama lengkap"><input id="name" name="name" value="{{ old('name') }}" class="input" required></x-field>
                <x-field name="email" label="Email sekolah"><input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required></x-field>
                <fieldset>
                    <legend class="label">Peran</legend>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['bk' => 'Guru BK', 'wali_kelas' => 'Wali Kelas'] as $v => $l)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-3 text-sm font-bold" :class="role === '{{ $v }}' ? 'card-select' : 'border-line'">
                                <input type="radio" name="role" value="{{ $v }}" x-model="role" class="accent-primary"> {{ $l }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div x-show="role === 'wali_kelas'" x-cloak>
                    <p class="label">Kelas asuhan <span class="text-danger">*</span></p>
                    <div class="flex flex-wrap gap-2 rounded-xl border-2 p-3 {{ $errors->has('classroom_ids') ? 'border-danger' : 'border-line' }}">
                        @foreach ($classrooms as $c)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="classroom_ids[]" value="{{ $c->id }}" x-model.number="picked" class="peer sr-only">
                                <span class="chip chip-gray peer-checked:border-primary peer-checked:bg-soft peer-checked:text-primary-dark">{{ $c->nama_kelas }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('classroom_ids')<p class="error-text">{{ $message }}</p>@enderror
                    <p class="help">Wali Kelas akan mendapat notifikasi khusus untuk laporan yang melibatkan kelas ini.</p>
                </div>

                <label class="flex cursor-pointer items-center gap-3 text-sm font-bold"><input type="checkbox" name="undangan" value="1" class="check" checked> Kirim undangan ke email</label>
            </div>
            <div class="mt-6 flex justify-end gap-3"><button type="button" class="btn btn-secondary" @click="modal = false">Batal</button><button class="btn btn-primary">Buat akun</button></div>
            <p class="mt-3 text-right text-xs text-muted">Tindakan ini dicatat.</p>
        </form>
    </div>
</div>
@endsection
