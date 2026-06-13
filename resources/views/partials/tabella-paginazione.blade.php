@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    $paginator = $paginator ?? null;
    $queryParams = $queryParams ?? [];
    $perPage = (int) ($perPage ?? 10);
@endphp
@if ($paginator)
    <div class="sq-tabella-paginazione-wrap">
        <form method="GET" action="{{ request()->url() }}" class="sq-tabella-paginazione-form">
            @foreach ($queryParams as $name => $value)
                @if (is_array($value))
                    @foreach ($value as $v)
                        <input type="hidden" name="{{ $name }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="sq-tabella-paginazione-bar">
                <label for="per_page_select" class="sq-filtri-label sq-m-0">Risultati per pagina</label>
                <select id="per_page_select" name="per_page" class="sq-filtri-select sq-tabella-per-page-select" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <span class="sq-text-muted sq-tabella-paginazione-info">
                    @if ($paginator->total() > 0)
                        Visualizzazione {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} di {{ $paginator->total() }}
                    @else
                        Nessun risultato
                    @endif
                </span>
            </div>
        </form>
        @if ($paginator->hasPages())
            <div class="sq-tabella-paginazione-links">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endif
