{{-- Grafik batang vertikal SVG. $points: [label => nilai] --}}
@props(['points' => [], 'height' => 200, 'color' => '#6A5AE0'])
@php
    $labels = array_keys($points); $vals = array_values($points); $n = max(count($vals), 1);
    $max = max(1, ...($vals ?: [1])); $top = max(4, (int) ceil($max / 4) * 4);
    $W = 520; $H = $height; $pl = 30; $pb = 26; $pt = 10; $bw = ($W - $pl - 8) / $n;
@endphp
<svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Grafik batang">
    @for ($g = 0; $g <= 4; $g++)
        @php $y = $pt + ($H - $pt - $pb) * (1 - $g / 4); @endphp
        <line x1="{{ $pl }}" x2="{{ $W - 8 }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#DAD6F2" stroke-dasharray="{{ $g ? '4 4' : '0' }}"/>
        <text x="{{ $pl - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="11" fill="#625E85">{{ round($top * $g / 4) }}</text>
    @endfor
    @foreach ($vals as $i => $v)
        @php $h = ($H - $pt - $pb) * $v / $top; $x = $pl + $i * $bw + $bw * 0.18; @endphp
        <rect x="{{ $x }}" y="{{ $H - $pb - $h }}" width="{{ $bw * 0.64 }}" height="{{ max($h, 0) }}" rx="6" fill="{{ $color }}"><title>{{ $labels[$i] }}: {{ $v }}</title></rect>
        <text x="{{ $x + $bw * 0.32 }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="11" fill="#625E85">{{ $labels[$i] }}</text>
    @endforeach
</svg>
