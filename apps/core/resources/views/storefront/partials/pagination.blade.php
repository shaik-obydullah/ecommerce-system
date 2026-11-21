@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
@endphp

@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination__item is-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__item is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination__item is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination__item" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="pagination__item is-disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
