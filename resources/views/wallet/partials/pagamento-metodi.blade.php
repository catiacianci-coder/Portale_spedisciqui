@php
    $numeroOrdine = $ricarica->numero_ordine_wallet ?? ('ORW-'.$ricarica->id);
@endphp
<section id="sezione-pagamento-ricarica" class="sq-mt-28 sq-ordini-tab-section ordine-pay-metodi-section">
    <div class="sq-pay-metodi-grid">
        @foreach ($metodiJson as $mj)
            @php
                $iconUrl = $mj['icon_url'] ?? null;
                $imponibileFmt = \App\Support\ImportoEuro::format((float) ($mj['imponibile'] ?? 0));
                $totaleFmt = \App\Support\ImportoEuro::format((float) ($mj['totale'] ?? 0));
                $cartaDisabilitata = ! empty($mj['is_carta']) && ! ($stripeConfigured ?? true);
                $disabilitato = $cartaDisabilitata;
                $metodoId = (int) $mj['id'];
                $checkoutUrl = match (true) {
                    ! empty($mj['is_bonifico']) => route('wallet.ricariche.pagamento.bonifico', ['ricarica' => $ricarica, 'metodo_pagamento_id' => $metodoId]),
                    ! empty($mj['is_carta']) => route('wallet.ricariche.pagamento.carta', ['ricarica' => $ricarica, 'metodo_pagamento_id' => $metodoId]),
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
                        @if ($cartaDisabilitata) title="Stripe non configurato"
                        @else title="Metodo non disponibile" @endif
                    >
                        @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                    </span>
                @endif
            </div>
        @endforeach
    </div>
</section>
