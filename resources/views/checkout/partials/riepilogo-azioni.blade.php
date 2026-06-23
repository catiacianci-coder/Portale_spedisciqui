@php
    $canProceed = auth()->check() && auth()->user()->hasVerifiedEmail();
    $standardCommissionPct = collect($metodiJson ?? [])
        ->filter(fn ($m) => empty($m['is_wallet']))
        ->max('pct') ?? 0;
@endphp

<section class="sq-checkout-riepilogo-grid">
    <span class="sq-checkout-prezzo-label">Totale (Wallet)</span>
    <span class="sq-checkout-prezzo-val">
        <strong><span id="checkout-totale-wallet">—</span></strong>
        <span class="sq-text-muted sq-text-14">IVA incl.</span>
    </span>

    <span class="sq-checkout-prezzo-label">Totale (Bonifico / Carte)</span>
    <span class="sq-checkout-prezzo-val">
        <strong><span id="checkout-totale-standard">—</span></strong>
        <span class="sq-text-muted sq-text-14">IVA incl.</span>
    </span>

    <span class="sq-checkout-riepilogo-grid-spacer" aria-hidden="true"></span>
    <div class="sq-checkout-actions sq-checkout-actions--compact">
        <form method="POST" action="{{ route('carrello.aggiungi') }}" id="form-carrello-checkout" class="sq-checkout-action-form">
            @csrf
            <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
            <input type="hidden" name="servizi_json" id="checkout-servizi-json" value="[]">
            @if ($ritiroDomicilio ?? false)
                <input type="hidden" name="data_ritiro" id="checkout-data-ritiro-cart" value="{{ old('data_ritiro', $dataRitiroSelezionata ?? '') }}">
            @endif
            <button type="submit" class="sq-btn-primary sq-checkout-action-btn sq-checkout-action-btn--compact" title="Aggiungi al carrello">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                Voglio aggiungere al carrello
            </button>
        </form>

        @if ($canProceed)
            <form method="POST" action="{{ route('checkout.conferma_ordine') }}" id="form-paga-spedizione" class="sq-checkout-action-form">
                @csrf
                <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
                <input type="hidden" name="servizi_json" id="checkout-servizi-json-ordine" value="[]">
                @if ($ritiroDomicilio ?? false)
                    <input type="hidden" name="data_ritiro" id="checkout-data-ritiro-pay" value="{{ old('data_ritiro', $dataRitiroSelezionata ?? '') }}">
                @endif
                <button type="submit" class="sq-btn-primary sq-checkout-action-btn sq-checkout-action-btn--compact sq-checkout-action-btn--accent" title="Crea ordine e paga dopo">
                    <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                    Voglio pagare questa spedizione
                </button>
            </form>
        @else
            <p class="sq-pay-metodi-note sq-m-0 sq-checkout-auth-note">
                @guest
                    Per creare l’ordine serve un account con email verificata. <a href="{{ route('login') }}">Accedi</a>
                @else
                    Verifica l’email del tuo account per procedere con l’ordine.
                @endguest
            </p>
        @endif
    </div>
</section>

<script type="application/json" id="checkout-prezzi-config">@json(['standardCommissionPct' => (float) $standardCommissionPct])</script>
