<dialog id="sq-bo-util-duplica-modal" class="sq-bo-ordini-modal sq-bo-util-duplica-modal">
    <form method="dialog" class="sq-bo-ordini-modal__head">
        <strong>Duplica parametro globale</strong>
        <button type="submit" class="sq-bo-ordini-modal__close" aria-label="Chiudi">&times;</button>
    </form>
    <form method="POST" id="sq-bo-util-duplica-form" class="sq-bo-ordini-modal__body">
        @csrf
        <p class="sq-bo-ordini-modal__intro">
            Crea una nuova versione del parametro con i dati copiati dal record selezionato.
            Il record originale verrà chiuso al giorno prima della nuova data di inizio validità.
        </p>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-duplica-denominazione">Denominazione</label>
            <input type="text" id="sq-bo-util-duplica-denominazione" name="denominazione" maxlength="160" required class="sq-bo-util-inp">
        </div>
        <div class="sq-bo-util-duplica-grid">
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-duplica-val-ass">Val. assoluto</label>
                <input type="number" step="0.0001" id="sq-bo-util-duplica-val-ass" name="valore_assoluto" class="sq-bo-util-inp">
            </div>
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-duplica-val-pct">Val. %</label>
                <input type="number" step="0.0001" id="sq-bo-util-duplica-val-pct" name="valore_percentuale" class="sq-bo-util-inp">
            </div>
        </div>
        <div class="sq-bo-util-duplica-grid">
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-duplica-inizio">Inizio validità <span class="sq-bo-util-duplica-req">*</span></label>
                <input type="date" id="sq-bo-util-duplica-inizio" name="inizio_validita" required class="sq-bo-util-inp">
            </div>
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-duplica-fine">Fine validità</label>
                <input type="date" id="sq-bo-util-duplica-fine" name="fine_validita" class="sq-bo-util-inp">
            </div>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-duplica-metodo">Metodo pagamento</label>
            <select id="sq-bo-util-duplica-metodo" name="id_metodo_pagamentos" class="sq-bo-util-inp">
                <option value="">—</option>
                @foreach ($metodiPagamento as $mp)
                    <option value="{{ $mp->id }}">{{ $mp->metodo_pagamento }}</option>
                @endforeach
            </select>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-duplica-testo">Valore testo</label>
            <textarea id="sq-bo-util-duplica-testo" name="valore_testo" rows="4" class="sq-bo-util-inp sq-bo-util-inp--area"></textarea>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-duplica-varie">Varie</label>
            <textarea id="sq-bo-util-duplica-varie" name="varie" rows="3" class="sq-bo-util-inp sq-bo-util-inp--area"></textarea>
        </div>
        <div class="sq-bo-ordini-modal__actions">
            <button type="button" id="sq-bo-util-duplica-cancel" class="sq-bo-btn-link sq-bo-btn-gray sq-bo-util-duplica-cancel">Annulla</button>
            <button type="submit" class="sq-bo-btn-link sq-bo-btn-green">Salva</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const modal = document.getElementById('sq-bo-util-duplica-modal');
    const form = document.getElementById('sq-bo-util-duplica-form');
    const cancelBtn = document.getElementById('sq-bo-util-duplica-cancel');
    if (!modal || !form) {
        return;
    }

    const fields = {
        denominazione: document.getElementById('sq-bo-util-duplica-denominazione'),
        valoreAssoluto: document.getElementById('sq-bo-util-duplica-val-ass'),
        valorePercentuale: document.getElementById('sq-bo-util-duplica-val-pct'),
        inizioValidita: document.getElementById('sq-bo-util-duplica-inizio'),
        fineValidita: document.getElementById('sq-bo-util-duplica-fine'),
        metodo: document.getElementById('sq-bo-util-duplica-metodo'),
        valoreTesto: document.getElementById('sq-bo-util-duplica-testo'),
        varie: document.getElementById('sq-bo-util-duplica-varie'),
    };

    const setValue = (el, value) => {
        if (!el) {
            return;
        }
        if (value === null || value === undefined) {
            el.value = '';
            return;
        }
        el.value = String(value);
    };

    const parsePayloadB64 = (b64) => {
        if (!b64) {
            return {};
        }
        try {
            const binary = atob(b64);
            const bytes = Uint8Array.from(binary, (ch) => ch.charCodeAt(0));
            return JSON.parse(new TextDecoder().decode(bytes));
        } catch (error) {
            return {};
        }
    };

    document.querySelectorAll('.js-util-duplica').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled || btn.hidden) {
                return;
            }

            const payload = parsePayloadB64(btn.getAttribute('data-payload-b64'));

            form.action = btn.getAttribute('data-duplica-url') || '';
            setValue(fields.denominazione, payload.denominazione);
            setValue(fields.valoreAssoluto, payload.valore_assoluto);
            setValue(fields.valorePercentuale, payload.valore_percentuale);
            setValue(fields.inizioValidita, payload.inizio_validita);
            setValue(fields.fineValidita, '');
            setValue(fields.metodo, payload.id_metodo_pagamentos ?? '');
            setValue(fields.valoreTesto, payload.valore_testo);
            setValue(fields.varie, payload.varie);

            modal.showModal();
            fields.inizioValidita?.focus();
        });
    });

    cancelBtn?.addEventListener('click', () => modal.close());
})();
</script>
