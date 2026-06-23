@php
    $iban = trim((string) ($iban ?? ''));
    $chiaveCausale = trim((string) ($chiaveCausale ?? ''));
    $prefix = $prefix ?? 'sq-bonifico-page';
@endphp

<p class="sq-ordine-pagamento-panel-text">
    Puoi pagare l&apos;ordine con un bonifico bancario sul conto indicato sotto.
</p>

<p class="sq-ordine-pagamento-panel-note">
    Copia il codice causale e incollalo nella causale del bonifico. Se codice o importo non corrispondono,
    non potremo associare il pagamento al tuo ordine.
</p>

<p class="sq-ordine-pagamento-panel-note">
    L&apos;ordine apparirà tra i <strong>Pagati</strong> al momento della registrazione nei nostri sistemi
    dell&apos;avvenuto accredito bancario.
</p>

<div class="sq-ordine-pagamento-bonifico-dati">
    <div class="sq-bonifico-copy-block">
        <span class="sq-bonifico-copy-label">IBAN</span>
        <div class="sq-bonifico-copy-row">
            <output class="sq-bonifico-copy-value" id="{{ $prefix }}-iban">{{ $iban !== '' ? $iban : '—' }}</output>
            @if ($iban !== '')
                <button
                    type="button"
                    class="sq-bonifico-copy-btn js-bonifico-copy"
                    data-copy-text="{{ $iban }}"
                    title="Copia IBAN"
                    aria-label="Copia IBAN"
                >
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </div>

    <div class="sq-bonifico-copy-block">
        <span class="sq-bonifico-copy-label">Codice causale</span>
        <div class="sq-bonifico-copy-row">
            <output class="sq-bonifico-copy-value" id="{{ $prefix }}-chiave">{{ $chiaveCausale !== '' ? $chiaveCausale : '—' }}</output>
            @if ($chiaveCausale !== '')
                <button
                    type="button"
                    class="sq-bonifico-copy-btn js-bonifico-copy"
                    data-copy-text="{{ $chiaveCausale }}"
                    title="Copia codice causale"
                    aria-label="Copia codice causale"
                >
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </div>
</div>

<form method="POST" action="{{ $formAction }}" class="sq-ordine-pagamento-panel-form">
    @csrf
    <input type="hidden" name="metodo_pagamento_id" value="{{ (int) ($metodoId ?? 0) }}">
    <button type="submit" class="sq-btn-primary sq-ordine-pagamento-submit-btn">
        Ho preso nota, continua
    </button>
</form>

<script>
(() => {
    document.querySelectorAll('.js-bonifico-copy').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = (btn.dataset.copyText || '').trim();
            if (text === '') return;
            try {
                await navigator.clipboard.writeText(text);
                btn.classList.add('sq-bonifico-copy-btn--ok');
                setTimeout(() => btn.classList.remove('sq-bonifico-copy-btn--ok'), 1200);
            } catch {
                window.prompt('Copia negli appunti:', text);
            }
        });
    });
})();
</script>
