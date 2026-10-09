@props(['label', 'value', 'unit' => null, 'icon' => 'info', 'tone' => 'soft', 'href' => null, 'hint' => null, 'valueClass' => ''])
@php
    $tiles = ['soft' => 'bg-soft text-primary-dark', 'mint' => 'bg-mint text-accent-text', 'danger' => 'bg-danger-soft text-danger-dark', 'warn' => 'bg-warning/25 text-ink'];
    $tile = $tiles[$tone] ?? $tiles['soft'];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card card-pad block ' . ($href ? 'hover:bg-bg' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-bold tracking-wider text-muted uppercase">{{ $label }}</p>
        <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl {{ $tile }}"><x-icon :name="$icon" :size="18" /></span>
    </div>
    <p class="mt-3 flex items-baseline gap-2 text-4xl font-bold {{ $valueClass }}">{{ $value }}@if ($unit)<span class="text-xs font-semibold text-muted">{{ $unit }}</span>@endif</p>
    @if ($hint || isset($extra))
        <div class="mt-2 text-xs font-semibold text-muted">{{ $hint }}{{ $extra ?? '' }}</div>
    @endif
</{{ $tag }}>
