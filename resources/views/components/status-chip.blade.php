@props(['status'])
@php [$label, $icon, $cls] = \App\Support\Ui::status($status); @endphp
<span {{ $attributes->merge(['class' => "chip $cls"]) }}><x-icon :name="$icon" :size="14" />{{ $label }}</span>
