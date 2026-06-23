<dialog id="sq-bo-util-nuovo-modal" class="sq-bo-ordini-modal sq-bo-util-duplica-modal">
    <form method="dialog" class="sq-bo-ordini-modal__head">
        <strong>Nuovo parametro globale</strong>
        <button type="submit" class="sq-bo-ordini-modal__close" aria-label="Chiudi">&times;</button>
    </form>
    <form method="POST" id="sq-bo-util-nuovo-form" action="{{ route('backoffice.utilities.parametri_globali.store') }}" class="sq-bo-ordini-modal__body">
        <input type="hidden" name="_form" value="nuovo_parametro">
        @csrf
        <p class="sq-bo-ordini-modal__intro">
            Compila i campi del nuovo parametro. La <strong>data di inizio validità</strong> è obbligatoria.
        </p>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-nuovo-denominazione">Denominazione</label>
            <input type="text" id="sq-bo-util-nuovo-denominazione" name="denominazione" maxlength="160" required class="sq-bo-util-inp" value="{{ old('denominazione') }}">
        </div>
        <div class="sq-bo-util-duplica-grid">
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-nuovo-val-ass">Val. assoluto</label>
                <input type="number" step="0.0001" id="sq-bo-util-nuovo-val-ass" name="valore_assoluto" class="sq-bo-util-inp" value="{{ old('valore_assoluto') }}">
            </div>
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-nuovo-val-pct">Val. %</label>
                <input type="number" step="0.0001" id="sq-bo-util-nuovo-val-pct" name="valore_percentuale" class="sq-bo-util-inp" value="{{ old('valore_percentuale') }}">
            </div>
        </div>
        <div class="sq-bo-util-duplica-grid">
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-nuovo-inizio">Inizio validità <span class="sq-bo-util-duplica-req">*</span></label>
                <input type="date" id="sq-bo-util-nuovo-inizio" name="inizio_validita" required class="sq-bo-util-inp" value="{{ old('inizio_validita') }}">
            </div>
            <div class="sq-bo-ordini-modal__field">
                <label for="sq-bo-util-nuovo-fine">Fine validità</label>
                <input type="date" id="sq-bo-util-nuovo-fine" name="fine_validita" class="sq-bo-util-inp" value="{{ old('fine_validita') }}">
            </div>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-nuovo-metodo">Metodo pagamento</label>
            <select id="sq-bo-util-nuovo-metodo" name="id_metodo_pagamentos" class="sq-bo-util-inp">
                <option value="">—</option>
                @foreach ($metodiPagamento as $mp)
                    <option value="{{ $mp->id }}" @selected((int) old('id_metodo_pagamentos') === (int) $mp->id)>{{ $mp->metodo_pagamento }}</option>
                @endforeach
            </select>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-nuovo-testo">Valore testo</label>
            <textarea id="sq-bo-util-nuovo-testo" name="valore_testo" rows="4" class="sq-bo-util-inp sq-bo-util-inp--area">{{ old('valore_testo') }}</textarea>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="sq-bo-util-nuovo-varie">Varie</label>
            <textarea id="sq-bo-util-nuovo-varie" name="varie" rows="3" class="sq-bo-util-inp sq-bo-util-inp--area">{{ old('varie') }}</textarea>
        </div>
        <div class="sq-bo-ordini-modal__actions">
            <button type="button" id="sq-bo-util-nuovo-cancel" class="sq-bo-btn-link sq-bo-btn-gray sq-bo-util-duplica-cancel">Annulla</button>
            <button type="submit" class="sq-bo-btn-link sq-bo-btn-green">Salva</button>
        </div>
    </form>
</dialog>

<script>
(() => {
    const modal = document.getElementById('sq-bo-util-nuovo-modal');
    const cancelBtn = document.getElementById('sq-bo-util-nuovo-cancel');
    const openBtn = document.getElementById('sq-bo-util-nuovo-open');
    if (!modal || !openBtn) {
        return;
    }

    openBtn.addEventListener('click', () => {
        if (openBtn.disabled || openBtn.hidden) {
            return;
        }
        modal.showModal();
        document.getElementById('sq-bo-util-nuovo-denominazione')?.focus();
    });

    cancelBtn?.addEventListener('click', () => modal.close());

    @if ($errors->any() && old('_form') === 'nuovo_parametro')
        modal.showModal();
    @endif
})();
</script>
