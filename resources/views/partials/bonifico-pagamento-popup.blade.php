@php
    $modalId = $modalId ?? 'sq-bonifico-modal';
    $formId = $formId ?? 'sq-bonifico-form';
    $iban = trim((string) ($iban ?? ''));
    $chiaveCausale = trim((string) ($chiaveCausale ?? ''));
    $chiavePlaceholder = trim((string) ($chiavePlaceholder ?? '—'));
@endphp

<div id="{{ $modalId }}" class="sq-modal sq-modal--bonifico" hidden>
    <div class="sq-modal-backdrop js-bonifico-modal-close" tabindex="-1" aria-hidden="true"></div>
    <div
        class="sq-modal-panel sq-modal-panel--bonifico"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $modalId }}-title"
    >
        <h2 id="{{ $modalId }}-title" class="sq-modal-title">Pagamento con bonifico bancario</h2>

        <p class="sq-modal-text sq-m-0">
            Puoi pagare l&apos;ordine con un bonifico bancario sul conto indicato sotto.
        </p>

        <p class="sq-modal-text sq-modal-text--note sq-m-0">
            Copia questo codice e incollalo nella causale del bonifico. Se il codice o l&apos;importo non corrispondono,
            i nostri sistemi non potranno associare il pagamento al tuo ordine.
        </p>

        <div class="sq-bonifico-copy-stack">
            <div class="sq-bonifico-copy-block">
                <span class="sq-bonifico-copy-label">IBAN</span>
                <div class="sq-bonifico-copy-row">
                    <output class="sq-bonifico-copy-value" id="{{ $modalId }}-iban">{{ $iban !== '' ? $iban : '—' }}</output>
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
                    <output class="sq-bonifico-copy-value js-bonifico-chiave-display" id="{{ $modalId }}-chiave">{{ $chiaveCausale !== '' ? $chiaveCausale : $chiavePlaceholder }}</output>
                    <button
                        type="button"
                        class="sq-bonifico-copy-btn js-bonifico-copy js-bonifico-copy-chiave"
                        data-copy-text="{{ $chiaveCausale }}"
                        title="Copia codice causale"
                        aria-label="Copia codice causale"
                        @if ($chiaveCausale === '') hidden @endif
                    >
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        @if ($soloLettura ?? false)
            <div class="sq-modal-actions sq-bonifico-modal-actions">
                <button type="button" class="sq-btn-primary sq-modal-btn js-bonifico-modal-close">Ho capito</button>
            </div>
        @else
            <form
                id="{{ $formId }}"
                method="POST"
                action="{{ $formAction }}"
                class="sq-modal-actions sq-bonifico-modal-actions"
            >
                @csrf
                {{ $formExtras ?? '' }}
                <input type="hidden" name="metodo_pagamento_id" id="{{ $formId }}-metodo-id" value="">
                <button type="button" class="sq-btn-secondary sq-modal-btn js-bonifico-modal-close">Annulla</button>
                <button type="submit" class="sq-btn-primary sq-modal-btn">Ho preso nota, continua</button>
            </form>
        @endif
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById(@json($modalId));
    if (!modal) {
        return;
    }

    const soloLettura = @json($soloLettura ?? false);
    const metodoInput = soloLettura
        ? null
        : document.getElementById(@json($formId) + '-metodo-id');
    const chiaveDisplay = modal.querySelector('.js-bonifico-chiave-display');
    const chiaveCopyBtn = modal.querySelector('.js-bonifico-copy-chiave');

    const closeModal = () => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sq-modal-open');
    };

    const openModal = (btn) => {
        const metodoId = btn.getAttribute('data-metodo-id') || '';
        const chiave = btn.getAttribute('data-chiave-causale') || '';
        const chiavePlaceholder = btn.getAttribute('data-chiave-placeholder') || '—';

        if (metodoInput) {
            metodoInput.value = metodoId;
        }

        if (chiaveDisplay) {
            chiaveDisplay.textContent = chiave !== '' ? chiave : chiavePlaceholder;
        }

        if (chiaveCopyBtn) {
            if (chiave !== '') {
                chiaveCopyBtn.hidden = false;
                chiaveCopyBtn.dataset.copyText = chiave;
            } else {
                chiaveCopyBtn.hidden = true;
                chiaveCopyBtn.dataset.copyText = '';
            }
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sq-modal-open');
    };

    document.querySelectorAll('.js-bonifico-open-modal[data-modal-id="' + @json($modalId) + '"]').forEach((btn) => {
        btn.addEventListener('click', () => openModal(btn));
    });

    modal.querySelectorAll('.js-bonifico-modal-close').forEach((el) => {
        el.addEventListener('click', () => closeModal());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    modal.querySelectorAll('.js-bonifico-copy').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = (btn.dataset.copyText || '').trim();
            if (text === '') {
                return;
            }

            try {
                await navigator.clipboard.writeText(text);
                btn.classList.add('sq-bonifico-copy-btn--ok');
                setTimeout(() => btn.classList.remove('sq-bonifico-copy-btn--ok'), 1200);
            } catch {
                window.prompt('Copia negli appunti:', text);
            }
        });
    });

    if (@json($autoOpen ?? false)) {
        openModal({
            getAttribute(name) {
                return {
                    'data-chiave-causale': @json($chiaveCausale),
                    'data-chiave-placeholder': @json($chiavePlaceholder),
                }[name] ?? null;
            },
        });
    }
})();
</script>
