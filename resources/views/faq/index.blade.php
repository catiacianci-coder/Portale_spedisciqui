@extends('layouts.app')

@section('content')
<div class="sq-faq-page-wrap">
    <div class="sq-page-1200">
        <div class="sq-faq-page">
            @if ($faqs->isEmpty())
                <div class="sq-faq-empty">Non ci sono ancora domande in questa pagina.</div>
            @else
                <div class="sq-faq-list" id="faq-accordion">
                    @foreach ($faqs as $faq)
                        <details class="sq-faq-item" data-faq-item @if ($loop->first) open @endif>
                            <summary>{{ $faq->question }}</summary>
                            <div class="sq-faq-answer">
                                <div class="sq-faq-answer-inner">{!! nl2br(e($faq->answer)) !!}</div>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@if ($faqs->isNotEmpty())
<script>
(function () {
    var root = document.getElementById('faq-accordion');
    if (!root) return;
    root.querySelectorAll('[data-faq-item]').forEach(function (el) {
        el.addEventListener('toggle', function () {
            if (!el.open) return;
            root.querySelectorAll('[data-faq-item]').forEach(function (other) {
                if (other !== el) other.removeAttribute('open');
            });
        });
    });
})();
</script>
@endif
@endsection
