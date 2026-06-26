@if ($paginator->hasPages())
    <nav class="sq-pagination" role="navigation" aria-label="Paginazione">
        <div class="sq-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="sq-pagination-btn is-disabled">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="sq-pagination-btn">{{ __('pagination.previous') }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="sq-pagination-btn">{{ __('pagination.next') }}</a>
            @else
                <span class="sq-pagination-btn is-disabled">{{ __('pagination.next') }}</span>
            @endif
        </div>

        <div class="sq-pagination-desktop">
            @if ($paginator->onFirstPage())
                <span class="sq-pagination-btn sq-pagination-btn--icon is-disabled" aria-hidden="true">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="sq-pagination-btn sq-pagination-btn--icon" aria-label="{{ __('pagination.previous') }}">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="sq-pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="sq-pagination-btn is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="sq-pagination-btn" aria-label="Vai alla pagina {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="sq-pagination-btn sq-pagination-btn--icon" aria-label="{{ __('pagination.next') }}">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="sq-pagination-btn sq-pagination-btn--icon is-disabled" aria-hidden="true">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
