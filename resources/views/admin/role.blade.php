@extends('layouts.staff')
@section('title', 'Role dan akses')
@section('heading', 'Role dan akses')
@section('subheading', 'Atur izin tiap peran per fitur. Perubahan baru berlaku setelah klik “Simpan perubahan”. Kunci sebuah peran supaya aksesnya tidak berubah tanpa sengaja.')

@section('content')
@php
    $roles = array_keys($cfg['roles']);
    $icon = fn ($v) => match ($v) { 'y' => ['check-circle', 'text-accent-text', 'Boleh'], 'r' => ['eye', 'text-primary-dark', 'Baca saja'], default => ['x-circle', 'text-muted/60', 'Tidak'] };
@endphp
@foreach ($cfg['roles'] as $r => $label)
    <form id="lock-{{ $r }}" method="post" action="{{ route('admin.role.kunci') }}" data-confirm="{{ $locked[$r] ? 'Buka kunci peran ' . $label . '? Aksesnya bisa diubah lagi.' : 'Kunci peran ' . $label . '? Aksesnya tidak bisa diubah sampai kunci dibuka.' }}" data-confirm-title="{{ $locked[$r] ? 'Buka kunci peran?' : 'Kunci peran?' }}" data-confirm-label="{{ $locked[$r] ? 'Buka kunci' : 'Kunci' }}">@csrf
        <input type="hidden" name="role" value="{{ $r }}"><input type="hidden" name="kunci" value="{{ $locked[$r] ? 0 : 1 }}">
    </form>
@endforeach
<form method="post" action="{{ route('admin.role.update') }}" x-data="{ dirty: false }" @change="dirty = true">
    @csrf
    <section class="card overflow-x-auto">
        <table class="tbl min-w-[720px]">
            <thead><tr><th class="w-1/3">Fitur</th>@foreach ($cfg['roles'] as $r => $label)<th class="text-center"><span class="block">{{ $label }}</span>
                <button type="submit" form="lock-{{ $r }}" class="mt-1 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold normal-case {{ $locked[$r] ? 'bg-warning/25 text-ink' : 'text-primary-dark hover:bg-soft' }}" title="{{ $locked[$r] ? 'Buka kunci peran ' . $label : 'Kunci peran ' . $label }}"><x-icon :name="$locked[$r] ? 'lock' : 'check-circle'" :size="12" />{{ $locked[$r] ? 'Terkunci' : 'Kunci' }}</button></th>@endforeach</tr></thead>
            @foreach ($cfg['groups'] as $group => $features)
                <tbody x-data="{ open: true }">
                    <tr class="cursor-pointer bg-soft/60" @click="open = ! open"><td colspan="{{ count($roles) + 1 }}" class="!py-2 text-xs font-bold tracking-wider text-primary-dark uppercase">
                        <span class="inline-flex items-center gap-2"><x-icon name="chevron-down" :size="14" />{{ $group }}@if ($group === 'AI Saran')<x-icon name="bot" :size="14" />@endif</span></td></tr>
                    @foreach ($features as $key => $row)
                        <tr x-show="open">
                            <td class="font-semibold">{{ $row[0] }}</td>
                            @foreach ($roles as $r)
                                @php $val = $values[$key][$r]; [$ic, $cl, $tt] = $icon($val); @endphp
                                <td class="text-center">
                                    @if ($editable[$key][$r])
                                        <label class="inline-flex cursor-pointer items-center gap-1.5" title="Dapat diubah admin">
                                            <input type="checkbox" name="p[{{ $key }}][{{ $r }}]" value="1" class="check" @checked($val !== 'n')>
                                            <span class="sr-only">{{ $row[0] }} untuk {{ $cfg['roles'][$r] }}</span>
                                            @if ($val === 'r')<x-icon name="eye" :size="14" class="text-primary-dark" aria-label="Baca saja" />@endif
                                        </label>
                                    @elseif ($applicable[$key][$r])
                                        <span class="inline-flex items-center gap-1 {{ $cl }}" title="{{ $tt }} · peran ini dikunci">
                                            <x-icon :name="$ic" :size="18" /><span class="sr-only">{{ $tt }}</span><x-icon name="lock" :size="11" class="text-muted/50" />
                                        </span>
                                    @else
                                        <span class="text-muted/50" title="Tidak berlaku untuk peran ini"><x-icon name="minus" :size="16" /><span class="sr-only">Tidak berlaku</span></span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endforeach
        </table>
    </section>

    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs font-semibold text-muted">
        <span class="inline-flex items-center gap-1"><input type="checkbox" class="check !size-4" checked disabled>Dapat diubah</span>
        <span class="inline-flex items-center gap-1"><x-icon name="check-circle" :size="14" class="text-accent-text" />Boleh</span>
        <span class="inline-flex items-center gap-1"><x-icon name="eye" :size="14" class="text-primary-dark" />Baca saja</span>
        <span class="inline-flex items-center gap-1"><x-icon name="x-circle" :size="14" />Tidak</span>
        <span class="inline-flex items-center gap-1"><x-icon name="lock" :size="12" />Peran sedang dikunci (kunci bisa dibuka di judul kolom)</span>
        <span class="inline-flex items-center gap-1"><x-icon name="minus" :size="14" />Tidak berlaku: fitur ini memang tidak tersedia bagi peran itu</span>
    </div>

    <div x-show="dirty" x-cloak class="fixed inset-x-4 bottom-4 z-30 mx-auto flex max-w-xl items-center justify-between gap-4 rounded-xl border-2 border-b-4 border-ink bg-ink p-3 pl-5 text-white">
        <span class="text-sm font-semibold">Ada perubahan yang belum disimpan. <span class="text-white/70">Tindakan ini dicatat.</span></span>
        <button class="btn btn-primary btn-sm">Simpan perubahan</button>
    </div>
</form>
@endsection
