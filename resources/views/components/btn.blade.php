@props(['variant' => 'primary', 'size' => null, 'href' => null, 'type' => 'button', 'icon' => null, 'iconRight' => null])
@php
    $cls = 'btn btn-' . $variant . ($size ? ' btn-' . $size : '');
    $isz = $size === 'sm' ? 16 : 18;
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if ($icon)<x-icon :name="$icon" :size="$isz" />@endif{{ $slot }}@if ($iconRight)<x-icon :name="$iconRight" :size="$isz" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if ($icon)<x-icon :name="$icon" :size="$isz" />@endif{{ $slot }}@if ($iconRight)<x-icon :name="$iconRight" :size="$isz" />@endif
    </button>
@endif
