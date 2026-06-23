<div class="sq-pay-metodo-card__icon-wrap">
    @if ($iconUrl)
        <img src="{{ $iconUrl }}" alt="" class="sq-pay-metodo-card__icon">
    @else
        <span class="sq-pay-metodo-card__icon-fallback" aria-hidden="true">
            <i class="fa-solid fa-credit-card"></i>
        </span>
    @endif
</div>

<span class="sq-pay-metodo-card__name">{{ $mj['nome'] }}</span>

<dl class="sq-pay-metodo-card__amounts">
    <div class="sq-pay-metodo-card__amount-row">
        <dt>Imponibile</dt>
        <dd @if (! empty($amountsDynamic)) class="js-pay-imponibile-fmt" @endif>{{ ! empty($amountsDynamic) ? '—' : $imponibileFmt }}</dd>
    </div>
    <div class="sq-pay-metodo-card__amount-row sq-pay-metodo-card__amount-row--total">
        <dt>Totale ivato</dt>
        <dd @if (! empty($amountsDynamic)) class="js-pay-totale-fmt" @endif>{{ ! empty($amountsDynamic) ? '—' : $totaleFmt }}</dd>
    </div>
</dl>
