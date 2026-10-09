@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginasi" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
        <p class="text-muted">Menampilkan <strong class="text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> dari <strong class="text-ink">{{ fmt_num($paginator->total()) }}</strong></p>
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm opacity-50"><x-icon name="chevron-left" :size="14" />Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm"><x-icon name="chevron-left" :size="14" />Sebelumnya</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-muted">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex size-9 items-center justify-center rounded-xl border-2 border-primary border-b-4 border-b-primary-dark bg-primary text-xs font-bold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex size-9 items-center justify-center rounded-xl border-2 border-b-4 border-line bg-white text-xs font-bold hover:bg-soft">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm">Berikutnya<x-icon name="chevron-right" :size="14" /></a>
            @else
                <span class="btn btn-secondary btn-sm opacity-50">Berikutnya<x-icon name="chevron-right" :size="14" /></span>
            @endif
        </div>
    </nav>
@endif
