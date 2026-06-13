@extends('layouts.app')

@section('content')
<div class="sq-legal-page-wrap">
    <div class="sq-page-1200">
        <div class="sq-legal-page">
            @if ($versoes->isEmpty())
                <div class="sq-legal-empty">{{ $emptyMessage }}</div>
            @else
                <div class="sq-legal-versions" id="legal-versions-accordion">
                    @foreach ($versoes as $i => $v)
                        <details class="sq-legal-acc sq-legal-acc--card" data-legal-version @if ($i === 0) open @endif>
                            <summary>
                                <span class="sq-legal-acc__summary-text">
                                    {{ $v->titulo }}
                                    <span class="sq-legal-meta">
                                        Valido dal {{ $v->vigente_desde?->format('d/m/Y') ?? '—' }}
                                        @if ($v->publicado_em)
                                            · Pubblicato il {{ $v->publicado_em->format('d/m/Y') }}
                                        @endif
                                    </span>
                                </span>
                            </summary>
                            <div class="sq-legal-body">
                                {!! $v->conteudo_html !!}
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@if ($versoes->isNotEmpty())
<script>
(function () {
    var root = document.getElementById('legal-versions-accordion');
    if (!root) return;
    root.querySelectorAll('[data-legal-version]').forEach(function (el) {
        el.addEventListener('toggle', function () {
            if (!el.open) return;
            root.querySelectorAll('[data-legal-version]').forEach(function (other) {
                if (other !== el) other.removeAttribute('open');
            });
        });
    });
})();
</script>
@endif
@endsection
