@props(['name' => '?', 'size' => 36, 'tone' => 'soft'])
@php
    $p = preg_split('/\s+/', trim($name));
    $ini = strtoupper(mb_substr($p[0] ?? '?', 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : ''));
    $tones = ['soft' => 'bg-soft text-primary-dark border-primary/50', 'mint' => 'bg-mint text-accent-text border-accent/50', 'gray' => 'bg-gray-soft text-muted border-line', 'warn' => 'bg-warning/25 text-ink border-warning'];
    $tone = $tones[$tone] ?? $tones['soft'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full border-2 text-xs font-bold $tone"]) }}
      style="width:{{ $size }}px;height:{{ $size }}px">{{ $ini }}</span>
