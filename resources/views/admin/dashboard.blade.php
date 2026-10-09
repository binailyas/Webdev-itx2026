@extends('layouts.staff')
@section('title', 'Dashboard')
@section('heading', 'Dashboard pengguna')
@section('subheading', 'Ringkasan akun dan aktivitas. Halaman ini hanya memuat angka agregat.')

@section('actions')
    <div class="tabs" role="tablist" aria-label="Rentang waktu">
        @foreach ([7, 30, 90] as $d)
            <a href="?rentang={{ $d }}" class="tab" @if ($days === $d) aria-current="page" @endif>{{ $d }} hari</a>
        @endforeach
    </div>
@endsection

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat label="Total akun terdaftar" :value="fmt_num($total)" unit="akun" icon="users" />
    <x-stat label="Aktif {{ $days }} hari terakhir" :value="fmt_num($days === 7 ? $aktif7 : \App\Models\User::where('last_login_at', '>=', now()->subDays($days))->count())" icon="user-check" tone="mint" />
    <x-stat label="Akun anonim aktif" :value="fmt_num($anon)" icon="eye-off" />
    <x-stat label="Login gagal hari ini" :value="$gagal" icon="alert-triangle" :tone="$gagal > 0 ? 'danger' : 'soft'" :value-class="$gagal > 0 ? 'text-danger' : ''" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
    <div class="space-y-6">
        <section class="card card-pad">
            <div class="mb-4 flex items-center justify-between border-b-2 border-line pb-3">
                <h2 class="text-lg">Pengguna aktif harian</h2>
                <span class="chip chip-soft"><span class="size-2 rounded-full bg-primary"></span>Login unik</span>
            </div>
            <x-line-chart :points="$chart" />
        </section>

        <section class="card card-pad">
            <div class="mb-4 flex items-center justify-between border-b-2 border-line pb-3">
                <h2 class="text-lg">Akun per peran</h2>
                <a href="{{ route('admin.role.index') }}" class="inline-flex items-center gap-1 text-sm font-bold text-primary-dark hover:underline">Kelola role<x-icon name="arrow-right" :size="14" /></a>
            </div>
            @php $sum = max(1, $perRole->sum('n')); $cols = ['bg-primary', 'bg-accent', 'bg-primary-dark', 'bg-ink']; @endphp
            <div class="flex h-7 overflow-hidden rounded-full border-2 border-line" role="img" aria-label="Proporsi akun per peran">
                @foreach ($perRole as $r)
                    <span class="{{ $cols[$loop->index] }}" style="width: {{ max(1.5, $r['n'] / $sum * 100) }}%" title="{{ $r['label'] }}: {{ $r['n'] }}"></span>
                @endforeach
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($perRole as $r)
                    <div class="rounded-xl border-2 border-line p-3">
                        <p class="flex items-center gap-2 text-xs font-semibold text-muted"><span class="size-2.5 rounded-full {{ $cols[$loop->index] }}"></span>{{ $r['label'] }}</p>
                        <p class="mt-1 text-xl font-bold">{{ fmt_num($r['n']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card card-pad">
            <div class="mb-4 flex items-center justify-between border-b-2 border-line pb-3">
                <h2 class="text-lg">Perlu perhatian</h2>
                <span class="chip chip-danger">{{ collect($attention)->filter()->count() }} tugas</span>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-3 rounded-xl border-2 border-line p-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-gray-soft text-muted"><x-icon name="mail" :size="18" /></span>
                    <div><p class="text-sm font-bold">{{ $attention['belum_login'] }} siswa belum pernah login</p><a href="{{ route('admin.siswa.index') }}" class="text-xs font-bold text-primary-dark hover:underline">Lihat daftar siswa</a></div>
                </li>
                <li class="flex items-start gap-3 rounded-xl border-2 border-line p-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-gray-soft text-muted"><x-icon name="clock" :size="18" /></span>
                    <div><p class="text-sm font-bold">{{ $attention['anon_hampir'] }} akun anonim hampir kedaluwarsa</p><p class="text-xs text-muted">Angka saja. Identitas tidak tersimpan.</p></div>
                </li>
                <li class="flex items-start gap-3 rounded-xl border-2 {{ $attention['wk_tanpa_kelas'] ? 'border-warning bg-warning/10' : 'border-line' }} p-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-warning/25"><x-icon name="school" :size="18" /></span>
                    <div><p class="text-sm font-bold">{{ $attention['wk_tanpa_kelas'] }} wali kelas tanpa kelas asuhan</p><a href="{{ route('admin.staf.index', ['tab' => 'wali']) }}" class="text-xs font-bold text-primary-dark hover:underline">Tetapkan kelas asuhan</a></div>
                </li>
                <li class="flex items-start gap-3 rounded-xl border-2 border-line p-3">
                    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-gray-soft text-muted"><x-icon name="shield-check" :size="18" /></span>
                    <div><p class="text-sm font-bold">{{ $attention['tanpa_2fa'] }} akun BK/Wali Kelas tanpa 2FA</p></div>
                </li>
            </ul>
        </section>
    </div>
</div>

<section class="card mt-6 overflow-hidden">
    <div class="flex items-center justify-between border-b-2 border-line p-5">
        <h2 class="text-lg">Aktivitas akun terbaru</h2>
        <a href="{{ route('admin.audit.index') }}" class="text-sm font-bold text-primary-dark hover:underline">Semua log</a>
    </div>
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Tindakan</th><th>Jenis</th></tr></thead>
            <tbody>
            @forelse ($activity as $a)
                @php [$lbl, $cls] = \App\Http\Controllers\Admin\AuditController::action($a->action); @endphp
                <tr>
                    <td class="whitespace-nowrap text-muted">{{ $a->created_at->translatedFormat('d M, H:i') }}</td>
                    <td class="font-semibold">{{ $a->user?->name ?? 'Sistem' }}</td>
                    <td>{{ str_replace(['.', '_'], ' ', $a->action) }}</td>
                    <td><span class="chip {{ $cls }}">{{ $lbl }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4"><x-empty title="Belum ada aktivitas" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
