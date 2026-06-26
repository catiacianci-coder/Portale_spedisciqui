<div id="sq-anagrafica-unchanged-modal" class="sq-modal" hidden>
    <div class="sq-modal-backdrop js-anagrafica-unchanged-close" tabindex="-1" aria-hidden="true"></div>
    <div
        class="sq-modal-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="sq-anagrafica-unchanged-title"
    >
        <h2 id="sq-anagrafica-unchanged-title" class="sq-modal-title">Anagrafica</h2>
        <p class="sq-modal-text sq-m-0">{{ \App\Services\Anagrafica\AnagraficaRevisioneService::MSG_NESSUNA_MODIFICA }}</p>
        <div class="sq-modal-actions">
            <button type="button" class="sq-btn-primary sq-modal-btn js-anagrafica-unchanged-close">OK</button>
        </div>
    </div>
</div>

<script>
(() => {
    const modal = document.getElementById('sq-anagrafica-unchanged-modal');
    if (!modal) {
        return;
    }

    let onCloseCallback = null;

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('sq-modal-open');
        const cb = onCloseCallback;
        onCloseCallback = null;
        if (typeof cb === 'function') {
            cb();
        }
    }

    function openModal(onClose) {
        onCloseCallback = onClose || null;
        modal.hidden = false;
        document.body.classList.add('sq-modal-open');
        modal.querySelector('.js-anagrafica-unchanged-close')?.focus();
    }

    modal.querySelectorAll('.js-anagrafica-unchanged-close').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    window.sqAnagraficaUnchangedModal = { open: openModal, close: closeModal };

    window.sqAnagraficaSnapshotsEqual = function sqAnagraficaSnapshotsEqual(rawA, rawB) {
        try {
            const normCap = (v) => {
                const digits = String(v ?? '').replace(/\D/g, '');
                if (digits === '') {
                    return '';
                }

                return digits.padStart(5, '0').slice(-5);
            };
            const normProv = (v) => String(v ?? '').trim().toUpperCase().slice(0, 2);
            const normStr = (v) => String(v ?? '').trim().toLowerCase();

            const normalize = (raw) => {
                const o = typeof raw === 'string' ? JSON.parse(raw) : raw;
                const out = {};
                Object.keys(o).sort().forEach((key) => {
                    let value = o[key];
                    if (key === 'cap') {
                        value = normCap(value);
                    } else if (key === 'provincia') {
                        value = normProv(value);
                    } else if (key === 'sede_liccardi') {
                        value = value === true || value === 1 || value === '1' ? '1' : '0';
                    } else {
                        value = normStr(value);
                    }
                    out[key] = value;
                });

                return out;
            };

            const a = normalize(rawA);
            const b = normalize(rawB);

            return JSON.stringify(a) === JSON.stringify(b);
        } catch (e) {
            return false;
        }
    };
})();
</script>
