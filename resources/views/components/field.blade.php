{{-- Pembungkus label + pesan galat per kolom --}}
@props(['name', 'label' => null, 'help' => null])
<div {{ $attributes->class(['min-w-0']) }}>
    @if ($label)<label for="{{ $name }}" class="label">{{ $label }}</label>@endif
    {{ $slot }}
    @error($name)<p class="error-text">{{ $message }}</p>@enderror
    @if ($help && ! $errors->has($name))<p class="help">{{ $help }}</p>@endif
</div>
