@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="mb-4 inline-flex size-16 items-center justify-center rounded-full border-2 border-line bg-soft text-primary"><x-icon :name="$icon" :size="28" /></span>
    <h3 class="text-lg font-bold">{{ $title }}</h3>
    @if ($text)<p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>@endif
    @if (trim($slot))<div class="mt-5">{{ $slot }}</div>@endif
</div>
