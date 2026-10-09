{{-- Grafik garis SVG (tanpa gradien/bayangan). $points: [label => nilai]; $series: opsional beberapa seri [nama => [label => nilai]] --}}
@props(['points' => [], 'series' => null, 'height' => 220, 'colors' => ['#6A5AE0', '#1FA88A', '#E0A23A']])
@php
    $series = $series ?? ['Nilai' => $points];
    $labels = array_keys(reset($series) ?: []);
    $n = max(count($labels), 1);
    $max = max(1, ...array_values(array_map(fn ($s) => $s ? max($s) : 0, $series)));
    $step = max(1, (int) ceil($max / 4)); $top = $step * 4;
    $W = 640; $H = $height; $pl = 36; $pr = 26; $pt = 12; $pb = 28;
    $x = fn ($i) => $pl + ($n === 1 ? ($W - $pl - $pr) / 2 : $i * (($W - $pl - $pr) / ($n - 1)));
    $y = fn ($v) => $pt + ($H - $pt - $pb) * (1 - $v / $top);
    $every = max(1, (int) ceil($n / 6));
@endphp
<svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full" role="img" aria-label="Grafik garis">
    @for ($g = 0; $g <= 4; $g++)
        @php $gv = $step * $g; @endphp
        <line x1="{{ $pl }}" x2="{{ $W - $pr }}" y1="{{ $y($gv) }}" y2="{{ $y($gv) }}" stroke="#DAD6F2" stroke-width="1" stroke-dasharray="{{ $g ? '4 4' : '0' }}"/>
        <text x="{{ $pl - 8 }}" y="{{ $y($gv) + 4 }}" text-anchor="end" font-size="11" fill="#625E85">{{ $gv }}</text>
    @endfor
    @foreach ($labels as $i => $lb)
        @if ($i % $every === 0 || $i === $n - 1)
            <text x="{{ $x($i) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="11" fill="#625E85">{{ $lb }}</text>
        @endif
    @endforeach
    @foreach ($series as $name => $pts)
        @php
            $vals = array_values($pts);
            $d = collect($vals)->map(fn ($v, $i) => ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($v), 1))->implode(' ');
            $c = $colors[$loop->index % count($colors)];
        @endphp
        <path d="{{ $d }}" fill="none" stroke="{{ $c }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
        @if ($n <= 31)
            @foreach ($vals as $i => $v)<circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="3" fill="#fff" stroke="{{ $c }}" stroke-width="2"><title>{{ $labels[$i] }}: {{ $v }}</title></circle>@endforeach
        @endif
    @endforeach
</svg>
@if (count($series) > 1)
    <ul class="mt-2 flex flex-wrap gap-4 text-xs font-semibold text-muted">
        @foreach (array_keys($series) as $name)<li class="flex items-center gap-1.5"><span class="size-2.5 rounded-full" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>{{ $name }}</li>@endforeach
    </ul>
@endif
