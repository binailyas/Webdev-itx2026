@extends('layouts.staff')
@section('title', 'Role dan akses')
@section('heading', 'Role dan akses')
@section('subheading', 'Matriks izin per fitur. Sel bergembok dikunci sistem demi privasi.')

@section('content')
@php
    $editable = $cfg['editable'];
    $roles = array_keys($cfg['roles']);
    $icon = fn ($v) => match ($v) { 'y' => ['check-circle', 'text-accent-text', 'Boleh'], 'r' => ['eye', 'text-primary-dark', 'Baca saja'], default => ['x-circle', 'text-muted/60', 'Tidak'] };
@endphp
<form method="post" action="{{ route('admin.role.update') }}" x-data="{ dirty: false }" @change="dirty = true">
    @csrf
    <section class="card overflow-x-auto">
        <table class="tbl min-w-[720px]">
            <thead><tr><th class="w-1/3">Fitur</th>@foreach ($cfg['roles'] as $r => $label)<th class="text-center">{{ $label }}</th>@endforeach</tr></thead>
            @foreach ($cfg['groups'] as $group => $features)
                <tbody x-data="{ open: true }">
                    <tr class="cursor-pointer bg-soft/60" @click="open = ! open"><td colspan="6" class="!py-2 text-xs font-bold tracking-wider text-primary-dark uppercase">
                        <span class="inline-flex items-center gap-2"><x-icon name="chevron-down" :size="14" />{{ $group }}@if ($group === 'AI Saran')<x-icon name="bot" :size="14" />@endif</span></td></tr>
                    @foreach ($features as $key => $row)
                        <tr x-show="open">
                            <td class="font-semibold">{{ $row[0] }}</td>
                            @foreach ($roles as $i => $r)
                                @php
                                    $val = $row[$i + 1];
                                    $skey = $editable[$key][$r] ?? null;
                                    [$ic, $cl, $tt] = $icon($val);
                                @endphp
                                <td class="text-center">
                                    @if ($skey)
                                        <label class="inline-flex cursor-pointer items-center gap-1.5" title="Kebijakan sekolah: dapat diubah">
                                            <input type="checkbox" name="p[{{ $key }}][{{ $r }}]" value="1" class="check" @checked(setting($skey, '1') === '1')>
                                            <span class="sr-only">{{ $row[0] }} untuk {{ $cfg['roles'][$r] }}</span>
                                        </label>
                                    @else
                                        <span class="inline-flex items-center gap-1 {{ $cl }}" title="{{ $tt }}{{ $val !== 'n' || true ? ' · dikunci sistem' : '' }}">
                                            <x-icon :name="$ic" :size="18" /><span class="sr-only">{{ $tt }}</span>
                                            <x-icon name="lock" :size="11" class="text-muted/50" />
                                        </span>
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
        <span class="inline-flex items-center gap-1"><x-icon name="check-circle" :size="14" class="text-accent-text" />Boleh</span>
        <span class="inline-flex items-center gap-1"><x-icon name="eye" :size="14" class="text-primary-dark" />Baca saja</span>
        <span class="inline-flex items-center gap-1"><x-icon name="x-circle" :size="14" />Tidak</span>
        <span class="inline-flex items-center gap-1"><x-icon name="lock" :size="12" />Dikunci sistem</span>
        <span class="inline-flex items-center gap-1"><input type="checkbox" class="check !size-4" checked disabled>Kebijakan sekolah (dapat diubah)</span>
    </div>

    <div x-show="dirty" x-cloak class="fixed inset-x-4 bottom-4 z-30 mx-auto flex max-w-xl items-center justify-between gap-4 rounded-xl border-2 border-b-4 border-ink bg-ink p-3 pl-5 text-white">
        <span class="text-sm font-semibold">Ada perubahan yang belum disimpan. <span class="text-white/70">Tindakan ini dicatat.</span></span>
        <button class="btn btn-primary btn-sm">Simpan perubahan</button>
    </div>
</form>
@endsection
