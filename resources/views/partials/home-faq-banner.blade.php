@if (Route::has('faq.index'))
    <div class="sq-home-premium-banner" role="region" aria-label="Domande frequenti">
        <span class="sq-home-premium-banner__icon" aria-hidden="true">
            <i class="fa-solid fa-circle-question"></i>
        </span>
        <div class="sq-home-premium-banner__text">
            <p class="sq-home-premium-banner__line sq-home-premium-banner__line--title">
                Hai dubbi o domande?
            </p>
            <p class="sq-home-premium-banner__line">
                Consulta le nostre <a href="{{ route('faq.index') }}" class="sq-home-premium-banner__link">FAQ</a>
            </p>
        </div>
    </div>
@endif
