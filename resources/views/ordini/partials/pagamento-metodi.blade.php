<section id="sezione-pagamento" class="sq-mt-28 sq-ordini-tab-section ordine-pay-metodi-section">
    <div class="sq-pay-metodi-grid">
        @foreach ($metodiJson as $mj)
            @php
                $iconUrl = $mj['icon_url'] ?? null;
                $imponibileFmt = \App\Support\ImportoEuro::format((float) ($mj['imponibile'] ?? 0));
                $totaleFmt = \App\Support\ImportoEuro::format((float) ($mj['totale'] ?? 0));
                $cartaDisabilitata = ! empty($mj['is_carta']) && ! ($stripeConfigured ?? true);
                $walletDisabilitato = ! empty($mj['is_wallet']) && ! ($walletSaldoOk ?? true);
                $disabilitato = $cartaDisabilitata || $walletDisabilitato;
                $metodoId = (int) $mj['id'];
                $checkoutUrl = match (true) {
                    ! empty($mj['is_wallet']) => route('ordini.pagamento.wallet', ['ordine' => $ordine, 'metodo_pagamento_id' => $metodoId]),
                    ! empty($mj['is_bonifico']) => route('ordini.pagamento.bonifico', ['ordine' => $ordine, 'metodo_pagamento_id' => $metodoId]),
                    ! empty($mj['is_carta']) => route('ordini.pagamento.carta', ['ordine' => $ordine, 'metodo_pagamento_id' => $metodoId]),
                    default => null,
                };
            @endphp

            <div class="sq-pay-metodo-tile">
                @if ($checkoutUrl && ! $disabilitato)
                    <a
                        href="{{ $checkoutUrl }}"
                        class="sq-pay-metodo-card sq-pay-metodo-card--link"
                        title="Paga con {{ $mj['nome'] }}"
                    >
                        @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                    </a>
                @else
                    <span
                        @class(['sq-pay-metodo-card', 'is-disabled'])
                        aria-disabled="true"
                        @if ($walletDisabilitato) title="Saldo Wallet insufficiente"
                        @elseif ($cartaDisabilitata) title="Stripe non configurato"
                        @else title="Metodo non disponibile" @endif
                    >
                        @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    @if (! ($walletSaldoOk ?? true))
        <p class="sq-pay-metodi-note">Il saldo Wallet non è sufficiente per il totale con questo metodo.</p>
    @endif
</section>
