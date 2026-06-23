@if (Route::has('tariffe_scontate.index'))
    <div class="sq-home-premium-banner" role="region" aria-label="Tariffe scontate">
        <span class="sq-home-premium-banner__icon" aria-hidden="true">
            <i class="fa-solid fa-truck-fast"></i>
        </span>
        <div class="sq-home-premium-banner__text">
            <p class="sq-home-premium-banner__line sq-home-premium-banner__line--title">
                Vuoi sapere come accedere ad una tariffa scontata?
            </p>
            <p class="sq-home-premium-banner__line">
                Compila subito il <a href="{{ route('tariffe_scontate.index') }}" class="sq-home-premium-banner__link">form</a>
            </p>
        </div>
    </div>
@endif
