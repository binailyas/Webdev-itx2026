@extends('layouts.base')

@php
    $user = auth()->user();
    $role = $user->role->name;
    $portal = ['admin' => 'Portal administrator', 'bk' => 'Portal guru BK', 'wali_kelas' => 'Portal wali kelas'][$role];
    $roleLabel = ['admin' => 'Admin sekolah', 'bk' => 'Guru BK', 'wali_kelas' => 'Wali kelas'][$role];
    $badges = \App\Support\Nav::badges($user);
    $items = \App\Support\Nav::items($user);
    $unread = $role === 'admin' ? 0 : \App\Models\UserNotification::where('user_id', $user->id)->whereNull('read_at')->count();
    $searchAction = match ($role) { 'admin' => route('admin.siswa.index'), default => sroute('laporan.index') };
    $searchHint = $role === 'admin' ? 'Cari data siswa, NIS, staf...' : 'Cari kode tiket atau judul laporan...';
@endphp

@section('body-class', 'min-h-screen')

@section('body')
<div x-data="{ drawer: false }" class="flex min-h-screen">
    {{-- Overlay mobile --}}
    <div x-show="drawer" x-cloak @click="drawer = false" class="fixed inset-0 z-30 bg-ink/40 lg:hidden"></div>

    {{-- Sidebar 264px --}}
    <aside :class="drawer ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-[264px] flex-col border-r-2 border-line bg-surface transition-transform lg:sticky lg:top-0 lg:h-screen lg:shrink-0">
        <div class="flex items-center gap-3 border-b-2 border-line/60 px-5 py-5">
            <span class="inline-flex size-11 items-center justify-center rounded-xl border-2 border-b-4 border-primary border-b-primary-dark bg-primary text-white"><x-icon name="heart" :size="22" /></span>
            <div class="leading-tight">
                <p class="text-lg font-extrabold">RuangDengar</p>
                <p class="text-[10px] font-bold tracking-wider text-primary-dark uppercase">{{ $portal }}</p>
            </div>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4" aria-label="Navigasi utama">
            @foreach ($items as [$label, $routeName, $icon, $badgeKey, $match])
                @php $active = collect(explode('|', $match))->contains(fn ($m) => request()->routeIs($m)); @endphp
                <a href="{{ route($routeName) }}" class="nav-item" @if ($active) aria-current="page" @endif>
                    <x-icon :name="$icon" :size="20" />
                    <span class="flex-1">{{ $label }}</span>
                    @if ($badgeKey && ($badges[$badgeKey] ?? 0) > 0)
                        @php $danger = $badgeKey === 'laporan' && ($badges['laporan_darurat'] ?? 0) > 0; @endphp
                        <span class="num-badge {{ $danger ? 'num-badge-danger' : '' }} {{ $active ? 'ring-2 ring-white/70' : '' }}" data-badge="{{ $badgeKey }}"
                              aria-label="{{ $label }}, {{ $badges[$badgeKey] }} belum dibuka">{{ $badges[$badgeKey] > 99 ? '99+' : $badges[$badgeKey] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="border-t-2 border-line/60 p-4">
            <form method="post" action="{{ route('logout') }}">@csrf
                <button class="nav-item w-full text-muted"><x-icon name="log-out" :size="20" />Keluar</button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Header atas --}}
        <header class="sticky top-0 z-20 flex h-[72px] items-center gap-3 border-b-2 border-line bg-surface px-4 md:px-8">
            <button class="btn-icon bg-soft !text-primary-dark lg:hidden" @click="drawer = true" aria-label="Buka menu"><x-icon name="menu" :size="20" /></button>
            <nav class="hidden items-center gap-2 text-sm font-semibold sm:flex" aria-label="Breadcrumb">
                <x-icon name="home" :size="16" class="text-muted" /><span class="text-muted">/</span><span>{{ $portal }}</span>
            </nav>
            <form action="{{ $searchAction }}" method="get" role="search" class="mx-auto hidden max-w-lg flex-1 md:block">
                <label class="relative block">
                    <span class="sr-only">Pencarian</span>
                    <x-icon name="search" :size="18" class="absolute top-1/2 left-4 -translate-y-1/2 text-muted" />
                    <input name="q" value="{{ request('q') }}" placeholder="{{ $searchHint }}" class="h-11 w-full rounded-xl border-2 border-line bg-gray-soft/60 pr-4 pl-11 text-sm font-medium placeholder:text-muted focus:border-primary focus:bg-white focus:outline-none">
                </label>
            </form>
            <div class="ml-auto flex items-center gap-3">
                @if ($role !== 'admin')
                    <a href="{{ sroute('notifikasi.index') }}" class="relative inline-flex size-11 items-center justify-center rounded-xl border-2 border-b-4 border-line bg-white hover:bg-soft" aria-label="Notifikasi{{ $unread ? ', ' . $unread . ' belum dibaca' : '' }}">
                        <x-icon name="bell" :size="20" />
                        @if ($unread)<span class="absolute -top-1.5 -right-1.5 num-badge num-badge-danger">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                    </a>
                @endif
                <div class="hidden h-9 w-0.5 bg-line sm:block"></div>
                <div class="relative" x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false">
                    <button @click="open = ! open" class="flex items-center gap-3 text-left" aria-haspopup="menu" :aria-expanded="open">
                        <span class="hidden leading-tight sm:block">
                            <span class="block text-sm font-bold">{{ $user->name }}</span>
                            <span class="chip chip-mint mt-0.5 h-5 text-[10px]">{{ $roleLabel }}</span>
                        </span>
                        <x-avatar :name="$user->name" :size="40" />
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-3 w-52 rounded-xl border-2 border-b-4 border-line bg-white p-2">
                        <p class="px-3 py-2 text-xs font-semibold break-all text-muted">{{ $user->email }}</p>
                        <form method="post" action="{{ route('logout') }}">@csrf
                            <button class="nav-item w-full"><x-icon name="log-out" :size="18" />Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1280px] flex-1 px-4 py-6 md:px-8 md:py-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    @hasSection('eyebrow')<p class="mb-1 text-[11px] font-bold tracking-wider text-primary-dark uppercase">@yield('eyebrow')</p>@endif
                    <h1 class="text-[28px] leading-tight">@yield('heading', View::yieldContent('title'))</h1>
                    @hasSection('subheading')<p class="mt-1 text-sm text-muted">@yield('subheading')</p>@endif
                </div>
                <div class="flex flex-wrap items-center gap-3">@yield('actions')</div>
            </div>
            @yield('content')
        </main>
    </div>
</div>

<x-confirm-dialog />

@if ($role !== 'admin')
    @push('scripts')
    <script>
        // Badge antrean bersama: polling ringan 30 detik (GET /badges).
        setInterval(async () => {
            try {
                const r = await fetch('{{ route('badges') }}', { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                document.querySelectorAll('[data-badge]').forEach(el => {
                    const n = d[el.dataset.badge] ?? 0;
                    el.textContent = n > 99 ? '99+' : n;
                    el.hidden = n === 0;
                });
            } catch (e) {}
        }, 30000);
    </script>
    @endpush
@endif
@endsection
