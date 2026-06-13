@php
    $record = $record ?? null;
@endphp

<div class="sq-bo-param-grid">
    <div>
        <label for="corriere_id" class="sq-label">Corriere</label>
        <select id="corriere_id" name="corriere_id" class="sq-bo-param-input" required>
            <option value="">— Seleziona —</option>
            @foreach ($corrieri as $c)
                <option value="{{ $c->id }}" @selected((int) old('corriere_id', $record?->corriere_id) === (int) $c->id)>
                    #{{ $c->id }} — {{ $c->nome_visualizzato ?: $c->nome_corriere }}
                </option>
            @endforeach
        </select>
        @error('corriere_id')
            <p class="sq-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="msg_ricevuto" class="sq-label">Msg ricevuto</label>
        <input
            type="text"
            id="msg_ricevuto"
            name="msg_ricevuto"
            value="{{ old('msg_ricevuto', $record?->msg_ricevuto) }}"
            class="sq-bo-param-input"
            maxlength="500"
            required
        >
        @error('msg_ricevuto')
            <p class="sq-field-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="msg_per_cliente" class="sq-label">Msg per cliente</label>
        <textarea id="msg_per_cliente" name="msg_per_cliente" rows="4" class="sq-bo-param-input" maxlength="500">{{ old('msg_per_cliente', $record?->msg_per_cliente) }}</textarea>
        <p class="sq-text-muted sq-font-sm sq-mt-8">Lascia vuoto per mostrare al cliente il messaggio ricevuto così com’è.</p>
        @error('msg_per_cliente')
            <p class="sq-field-error">{{ $message }}</p>
        @enderror
    </div>
</div>
