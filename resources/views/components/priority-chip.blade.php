@props(['priority'])
@php [$label, $icon, $cls] = \App\Support\Ui::priority($priority); @endphp
<span {{ $attributes->merge(['class' => "chip $cls"]) }}><x-icon :name="$icon" :size="14" />{{ $label }}</span>
