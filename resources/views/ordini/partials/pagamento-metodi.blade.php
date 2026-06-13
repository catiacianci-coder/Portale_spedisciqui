@php
    use App\Models\parametri_globali;

    $ibanBonifico = parametri_globali::valoreTesto(parametri_globali::DENOM_IBAN_CC_R_B);
    $haBonifico = collect($metodiJson)->contains(fn ($m) => ! empty($m['is_bonifico']));
@endphp
<section id="sezione-pagamento" class="sq-mt-28 sq-ordini-tab-section ordine-pay-metodi-section">

    <div class="sq-pay-metodi-grid">
        @foreach ($metodiJson as $mj)
            @php
                $iconUrl = $mj['icon_url'] ?? null;
                $imponibileFmt = number_format((float) ($mj['imponibile'] ?? 0), 2, ',', '.');
                $totaleFmt = number_format((float) ($mj['totale'] ?? 0), 2, ',', '.');
                $cartaDisabilitata = ! empty($mj['is_carta']) && ! ($stripeConfigured ?? true);
                $walletDisabilitato = ! empty($mj['is_wallet']) && ! ($walletSaldoOk ?? true);
                $disabilitato = $cartaDisabilitata || $walletDisabilitato;
            @endphp

            @if (! empty($mj['is_wallet']))
                <button
                    type="button"
                    @class(['sq-pay-metodo-card', 'js-wallet-open-modal', 'is-disabled' => $disabilitato])
                    data-metodo-id="{{ (int) $mj['id'] }}"
                    data-codice="{{ $ordine->codice }}"
                    data-totale-label="{{ $totaleFmt }}"
                    @if ($walletDisabilitato) disabled title="Saldo Wallet insufficiente" @else title="Paga con {{ $mj['nome'] }}" @endif
                >
                    @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                </button>
            @elseif (! empty($mj['is_bonifico']))
                <button
                    type="button"
                    class="sq-pay-metodo-card js-bonifico-open-modal"
                    data-modal-id="sq-bonifico-modal"
                    data-metodo-id="{{ (int) $mj['id'] }}"
                    data-chiave-causale="{{ $ordine->chiave_causale }}"
                    title="Paga con {{ $mj['nome'] }}"
                >
                    @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                </button>
            @else
                <form method="POST" action="{{ route('ordini.pagamento', $ordine) }}" class="sq-pay-metodo-card-form">
                    @csrf
                    <input type="hidden" name="metodo_pagamento_id" value="{{ $mj['id'] }}">
                    <button
                        type="submit"
                        @class(['sq-pay-metodo-card', 'is-disabled' => $disabilitato])
                        @if ($cartaDisabilitata) disabled title="Stripe non configurato" @else title="Paga con {{ $mj['nome'] }}" @endif
                    >
                        @include('ordini.partials.pagamento-metodo-card-inner', compact('mj', 'iconUrl', 'imponibileFmt', 'totaleFmt'))
                    </button>
                </form>
            @endif
        @endforeach
    </div>

    @if (! ($walletSaldoOk ?? true))
        <p class="sq-pay-metodi-note">Il saldo Wallet non è sufficiente per il totale con questo metodo.</p>
    @endif
</section>

<div id="sq-wallet-modal" class="sq-modal" hidden data-wallet-modal>
    <div class="sq-modal-backdrop js-wallet-modal-close" tabindex="-1" aria-hidden="true"></div>
    <div
        class="sq-modal-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sq-wallet-modal-title"
    >
        <h2 id="sq-wallet-modal-title" class="sq-modal-title">Pagamento con Wallet</h2>
        <p class="sq-modal-text sq-m-0 sq-mb-16" id="sq-wallet-modal-body"></p>
        <form method="POST" action="{{ route('ordini.pagamento', $ordine) }}" class="sq-modal-actions">
            @csrf
            <input type="hidden" name="metodo_pagamento_id" id="sq-wallet-metodo-id" value="">
            <input type="hidden" name="conferma_wallet" value="1">
            <button type="button" class="sq-btn-primary sq-modal-btn js-wallet-modal-close">Annulla</button>
            <button type="submit" class="sq-btn-primary sq-modal-btn">Conferma</button>
        </form>
    </div>
</div>

@if ($haBonifico)
    @include('partials.bonifico-pagamento-popup', [
        'modalId' => 'sq-bonifico-modal',
        'formId' => 'sq-bonifico-form',
        'formAction' => route('ordini.pagamento', $ordine),
        'iban' => $ibanBonifico,
        'chiaveCausale' => $ordine->chiave_causale,
    ])
@endif

<script>
(() => {
    const modal = document.querySelector('[data-wallet-modal]');
    if (!modal) return;

    const bodyEl = document.getElementById('sq-wallet-modal-body');
    const metodoInput = document.getElementById('sq-wallet-metodo-id');
    const openButtons = document.querySelectorAll('.js-wallet-open-modal');

    const closeModal = () => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
    };

    const openModal = (btn) => {
        const id = btn.getAttribute('data-metodo-id');
        const codice = btn.getAttribute('data-codice') || '';
        const totale = btn.getAttribute('data-totale-label') || '—';
        if (metodoInput) metodoInput.value = id || '';
        if (bodyEl) {
            bodyEl.textContent = 'Stai per pagare l’ordine ' + codice + ' per un totale di ' + totale + ' € (IVA inclusa).';
        }
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
    };

    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            openModal(btn);
        });
    });

    modal.querySelectorAll('.js-wallet-modal-close').forEach((el) => {
        el.addEventListener('click', () => closeModal());
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
