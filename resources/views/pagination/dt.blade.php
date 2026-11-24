@if ($paginator->hasPages())
    <nav class="dt-pages" role="navigation" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="dt-pg is-off" aria-disabled="true"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Sebelumnya</span></span>
        @else
            <a class="dt-pg" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="dt-pg is-gap" aria-hidden="true">&hellip;</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="dt-pg is-on" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="dt-pg" href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="dt-pg" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        @else
            <span class="dt-pg is-off" aria-disabled="true"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Berikutnya</span></span>
        @endif
    </nav>
@endif
