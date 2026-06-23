<div class="sq-bo-corrieri-kv-row">
    <label for="{{ $inputId }}" class="sq-bo-corrieri-kv-label">{{ $meta['label'] }}</label>
    <div class="sq-bo-corrieri-kv-value">
        @include('backoffice.corrieri.partials.input-campo', [
            'corriere' => $corriere,
            'campoKey' => $campoKey,
            'meta' => $meta,
            'tipoOdOptions' => $tipoOdOptions,
            'ricarichi' => $ricarichi,
            'name' => $name ?? $campoKey,
            'idPrefix' => $idPrefix ?? ('corriere-'.$corriere->id.'-'.$campoKey),
        ])
        @if (!empty($meta['hint']))
            <p class="sq-bo-corrieri-kv-hint">{{ $meta['hint'] }}</p>
        @endif
    </div>
</div>
