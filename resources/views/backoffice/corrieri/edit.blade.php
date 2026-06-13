@extends('layouts.app')

@section('pageBanner')
    <x-sq-page-banner
        variant="backoffice"
        :title="'Etichette punto — ' . ($corriere->nome_visualizzato ?: $corriere->nome_corriere)"
        icon="fa-truck-fast"
        :parent-href="route('backoffice.corrieri.index')"
        class="sq-page-banner--full"
    />
@endsection

@section('content')
<div class="sq-page-1100 backoffice-corrieri-edit">
    <p class="sq-mb-14">
        <a href="{{ route('backoffice.corrieri.index') }}" class="sq-header-link">← Elenco corrieri</a>
    </p>

    <p class="sq-intro sq-mb-18">
        Corriere #{{ $corriere->id }}
        @if ($corriere->codice_servizio)
            · <code class="sq-code">{{ $corriere->codice_servizio }}</code>
        @endif
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>
    @endif

    <div class="sq-bo-param-grid sq-mb-24">
        <div>
            <span class="sq-bo-param-label">Modalità ritiro</span>
            <p class="sq-m-0">{{ $corriere->pickup ?: '—' }}</p>
        </div>
        <div>
            <span class="sq-bo-param-label">Modalità consegna</span>
            <p class="sq-m-0">{{ $corriere->consegna ?: '—' }}</p>
        </div>
        <div>
            <span class="sq-bo-param-label">Piattaforma</span>
            <p class="sq-m-0">{{ $corriere->piattaforma ?: '—' }}</p>
        </div>
        <div>
            <span class="sq-bo-param-label">Stato</span>
            <p class="sq-m-0">{{ $corriere->attivo ? 'Attivo' : 'Disattivo' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('backoffice.corrieri.update', $corriere) }}" class="sq-bo-param-form">
        @csrf

        <div class="sq-bo-param-grid sq-mb-24">
            <div>
                <label class="sq-bo-param-label sq-d-flex sq-align-center sq-gap-8">
                    <input type="checkbox" name="trackingsn" value="1" @checked(old('trackingsn', $corriere->trackingsn))>
                    Tracking automatico (API)
                </label>
                <p class="sq-text-muted sq-font-sm sq-mt-8 sq-mb-0">
                    Se attivo, il portale interroga l’API del fornitore (Sendcloud / Liccardi TMS). Altrimenti mostra il link alla pagina del corriere.
                </p>
            </div>
            <div>
                <label for="url_tracking" class="sq-bo-param-label">URL pagina tracking corriere</label>
                <input id="url_tracking" name="url_tracking" type="text" maxlength="512" class="sq-bo-param-input"
                       value="{{ old('url_tracking', $corriere->url_tracking) }}"
                       placeholder="Es. https://www.gls-italy.com/?tracking={tracking}">
                <p class="sq-text-muted sq-font-sm sq-mt-8 sq-mb-0">
                    Usato quando il tracking automatico è disattivo. Puoi usare il segnaposto <code class="sq-code">{tracking}</code>.
                </p>
                @error('url_tracking')
                    <div class="sq-field-error">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label for="punto_ritiro" class="sq-bo-param-label">Punto ritiro (preventivi)</label>
                <input id="punto_ritiro" name="punto_ritiro" type="text" maxlength="255" class="sq-bo-param-input"
                       value="{{ old('punto_ritiro', $corriere->punto_ritiro) }}"
                       placeholder="Es. Vedi i punti Poste vicini a te">
                <p class="sq-text-muted sq-font-sm sq-mt-8 sq-mb-0">
                    Link in preventivi solo se il ritiro del corriere non è a domicilio (campo pickup).
                    Solo visualizzazione punti vicino al mittente.
                </p>
                @error('punto_ritiro')
                    <div class="sq-field-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="punto_consegna" class="sq-bo-param-label">Punto consegna (checkout)</label>
                <input id="punto_consegna" name="punto_consegna" type="text" maxlength="255" class="sq-bo-param-input"
                       value="{{ old('punto_consegna', $corriere->punto_consegna) }}"
                       placeholder="Es. Seleziona un locker InPost vicino a te">
                <p class="sq-text-muted sq-font-sm sq-mt-8 sq-mb-0">
                    Titolo e selezione obbligatoria nel checkout (CAP destinatario). Non compare in preventivi.
                </p>
                @error('punto_consegna')
                    <div class="sq-field-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="sq-bo-param-actions">
            <button type="submit" class="sq-btn-primary">Salva etichette</button>
            <a href="{{ route('backoffice.corrieri.index') }}" class="sq-btn-secondary sq-ml-12">Annulla</a>
        </div>
    </form>
</div>
@endsection
