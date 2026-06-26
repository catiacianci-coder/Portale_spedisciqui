@if ($selectedUser ?? null)
<dialog id="bo-wallet-nuovo-movimento-modal" class="sq-bo-ordini-modal">
    <form method="dialog" class="sq-bo-ordini-modal__head">
        <strong>Nuovo movimento wallet</strong>
        <button type="submit" class="sq-bo-ordini-modal__close" aria-label="Chiudi">&times;</button>
    </form>
    <form
        method="POST"
        id="bo-wallet-nuovo-movimento-form"
        class="sq-bo-ordini-modal__body"
        action="{{ route('backoffice.wallet.movimento.store', $selectedUser) }}"
    >
        @csrf
        @foreach ($queryParams ?? [] as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <p class="sq-bo-ordini-modal__intro">
            Cliente <strong>{{ $selectedUser->email }}</strong>
        </p>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-wallet-movimento-tipo">Tipo</label>
            <select
                id="bo-wallet-movimento-tipo"
                name="tipo"
                required
                class="sq-wallet-extrato-filtri__select"
            >
                <option value="" disabled @selected(old('tipo') === null || old('tipo') === '')>Seleziona tipo…</option>
                <option value="credito" @selected(old('tipo') === 'credito')>Credito</option>
                <option value="debito" @selected(old('tipo') === 'debito')>Debito</option>
            </select>
        </div>
        <div class="sq-bo-ordini-modal__field sq-wallet-movimento-desc-wrap" id="bo-wallet-movimento-desc-wrap" hidden>
            <label for="bo-wallet-movimento-desc">Dettaglio</label>
            <select
                id="bo-wallet-movimento-desc"
                name="wallet_descrizione_id"
                required
                disabled
                class="sq-wallet-extrato-filtri__select"
            >
                <option value="" disabled selected>Seleziona dettaglio…</option>
            </select>
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-wallet-movimento-riferimento">Ordine/LdV</label>
            <input
                type="text"
                id="bo-wallet-movimento-riferimento"
                name="riferimento"
                value="{{ old('riferimento') }}"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Riferimento visibile al cliente"
                class="sq-wallet-extrato-filtri__select"
            >
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-wallet-movimento-importo">Importo (€)</label>
            <input
                type="number"
                id="bo-wallet-movimento-importo"
                name="importo"
                value="{{ old('importo') }}"
                required
                min="0.01"
                max="999999.99"
                step="0.01"
                inputmode="decimal"
                placeholder="0,00"
                class="sq-wallet-extrato-filtri__select"
            >
        </div>
        <div class="sq-bo-ordini-modal__field">
            <label for="bo-wallet-movimento-nota-interna">
                Nota interna
                <span class="sq-bo-ordini-modal__hint">(solo backoffice, non visibile al cliente)</span>
            </label>
            <textarea
                id="bo-wallet-movimento-nota-interna"
                name="nota_interna"
                maxlength="500"
                rows="3"
                placeholder="Note per gli operatori…"
                class="sq-wallet-extrato-filtri__select sq-wallet-movimento-nota-interna"
            >{{ old('nota_interna') }}</textarea>
        </div>
        <div class="sq-bo-ordini-modal__actions">
            <button type="button" id="bo-wallet-movimento-cancelar" class="sq-bo-ordini-btn-anular">Annulla</button>
            <button type="submit" class="sq-bo-ordini-btn-paga">Registra movimento</button>
        </div>
    </form>
</dialog>
@endif
