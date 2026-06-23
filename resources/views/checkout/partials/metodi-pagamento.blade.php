<section class="sq-checkout-pay-metodi ordine-pay-metodi-section">
    <div class="sq-pay-metodi-grid" id="checkout-metodi-body">
        @foreach ($metodiJson as $mj)
            @php
                $iconUrl = $mj['icon_url'] ?? null;
                $cartaDisabilitata = ! empty($mj['is_carta']) && ! ($stripeConfigured ?? true);
                $canPay = auth()->check() && auth()->user()->hasVerifiedEmail();
            @endphp

            <div
                class="js-metodo-row sq-checkout-metodo-item"
                data-pct="{{ $mj['pct'] }}"
                data-abs="{{ $mj['abs'] }}"
                data-is-wallet="{{ ! empty($mj['is_wallet']) ? '1' : '0' }}"
            >
                @if ($canPay)
                    @if (! empty($mj['is_wallet']))
                        <button
                            type="button"
                            class="sq-pay-metodo-card js-wallet-open-modal-checkout js-checkout-wallet-btn"
                            data-metodo-id="{{ (int) $mj['id'] }}"
                            data-totale-label=""
                            title="Paga con {{ $mj['nome'] }}"
                        >
                            @include('ordini.partials.pagamento-metodo-card-inner', [
                                'mj' => $mj,
                                'iconUrl' => $iconUrl,
                                'imponibileFmt' => '',
                                'totaleFmt' => '',
                                'amountsDynamic' => true,
                            ])
                        </button>
                    @elseif (! empty($mj['is_bonifico']))
                        <button
                            type="button"
                            class="sq-pay-metodo-card js-bonifico-open-modal"
                            data-modal-id="sq-checkout-bonifico-modal"
                            data-metodo-id="{{ (int) $mj['id'] }}"
                            data-chiave-causale=""
                            data-chiave-placeholder="Generato alla conferma"
                            title="Paga con {{ $mj['nome'] }}"
                        >
                            @include('ordini.partials.pagamento-metodo-card-inner', [
                                'mj' => $mj,
                                'iconUrl' => $iconUrl,
                                'imponibileFmt' => '',
                                'totaleFmt' => '',
                                'amountsDynamic' => true,
                            ])
                        </button>
                    @else
                        <form method="POST" action="{{ route('checkout.paga') }}" class="sq-pay-metodo-card-form js-checkout-paga-form">
                            @csrf
                            <input type="hidden" name="corriere_id" value="{{ $corriereId }}">
                            <input type="hidden" name="servizi_json" class="js-checkout-servizi-hidden" value="[]">
                            <input type="hidden" name="metodo_pagamento_id" value="{{ (int) $mj['id'] }}">
                            <button
                                type="submit"
                                @class(['sq-pay-metodo-card', 'is-disabled' => $cartaDisabilitata])
                                @if ($cartaDisabilitata) disabled title="Stripe non configurato" @else title="Paga con {{ $mj['nome'] }}" @endif
                            >
                                @include('ordini.partials.pagamento-metodo-card-inner', [
                                    'mj' => $mj,
                                    'iconUrl' => $iconUrl,
                                    'imponibileFmt' => '',
                                    'totaleFmt' => '',
                                    'amountsDynamic' => true,
                                ])
                            </button>
                        </form>
                    @endif
                @else
                    <div class="sq-pay-metodo-card is-disabled" aria-disabled="true">
                        @include('ordini.partials.pagamento-metodo-card-inner', [
                            'mj' => $mj,
                            'iconUrl' => $iconUrl,
                            'imponibileFmt' => '',
                            'totaleFmt' => '',
                            'amountsDynamic' => true,
                        ])
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @guest
        <p class="sq-pay-metodi-note">Per pagare serve un account con email verificata. <a href="{{ route('login') }}">Accedi</a></p>
    @endguest
    @auth
        @unless (auth()->user()->hasVerifiedEmail())
            <p class="sq-pay-metodi-note">Verifica l’email del tuo account per procedere al pagamento.</p>
        @endunless
    @endauth
</section>
