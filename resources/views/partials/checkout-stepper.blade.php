{{-- Stepper flusso acquisto etichetta: solo indicazione visiva, senza link (evita confusione con la navigazione). --}}
@php
    $current = (int) ($step ?? 0);
    $steps = [
        1 => ['label' => 'Compilazione Spedizione'],
        2 => ['label' => 'Preventivi'],
        3 => ['label' => 'Indirizzi'],
        4 => ['label' => 'Pagamento'],
        5 => ['label' => 'Carrello'],
    ];
@endphp

@if ($current >= 2 && $current <= 5)
<nav class="sq-checkout-stepper" aria-label="Passi per completare la spedizione">
    <ol class="sq-checkout-stepper-list">
        @foreach ($steps as $num => $meta)
            @php
                $state = $num < $current ? 'done' : ($num === $current ? 'current' : 'todo');
            @endphp
            <li class="sq-checkout-stepper-li" @if ($num === $current) aria-current="step" @endif>
                <span class="sq-checkout-stepper-item sq-checkout-stepper-item--{{ $state }}">
                    <span class="sq-checkout-stepper-dot" aria-hidden="true">{{ $num }}</span>
                    <span class="sq-checkout-stepper-label">{{ $meta['label'] }}</span>
                </span>
                @unless ($loop->last)
                    <span class="sq-checkout-stepper-connector sq-checkout-stepper-connector--{{ $num < $current ? 'done' : 'todo' }}" aria-hidden="true"></span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
@endif
