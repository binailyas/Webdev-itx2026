@props(['name', 'size' => 20])
{!! \App\Support\Icons::svg($name, (int) $size, $attributes->get('class', '')) !!}
