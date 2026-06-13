@extends('layouts.app')
@section('content')
@php
    $sped = $spedizioneFound ?? null;
    $trackingInput = old('tracking', $searchInput['tracking'] ?? '');
    $codiceInput = old('codice_interno', $searchInput['codice_interno'] ?? '');
@endphp

<div class="sq-page-960-tight">
    <h1 class="sq-h1-carrello sq-text-heading sq-mb-12">Gestione resi</h1>

    @if ($errors->has('resi'))
        <div class="sq-alert sq-alert--error sq-mb-14">{{ $errors->first('resi') }}</div>
    @endif

    <form method="POST" action="{{ route('resi.search') }}" class="sq-card sq-card--p-14-16 sq-card--mb-14">
        @csrf
        <p class="sq-m-0 sq-mb-12 sq-text-13" style="margin-bottom:27px;">
            Inserisci il numero di tracking oppure il codice spedizione (senza prefisso).
        </p>
        <div class="sq-indirizzi-grid-2 sq-mb-12">
            <div>
                <label for="tracking" class="sq-indirizzi-label sq-indirizzi-label--main">Tracking</label>
                <input id="tracking" name="tracking" type="text" maxlength="128" class="sq-indirizzi-input"
                       value="{{ e($trackingInput) }}" placeholder="Inserisci tracking">
            </div>
            <div>
                <label for="codice_interno" class="sq-indirizzi-label sq-indirizzi-label--main">Coodice Spedizione</label>
                <input id="codice_interno" name="codice_interno" type="text" maxlength="40" class="sq-indirizzi-input"
                       value="{{ e($codiceInput) }}" placeholder="Codice Spedizione">
            </div>
        </div>
        <button type="submit" class="sq-btn-primary">Apri spedizione</button>
    </form>

    @if ($sped)
        @php
            $mitt = \App\Support\SpedizioneCampiPersistenza::mittenteArray($sped);
            $dest = is_array($sped->destinatario_json) ? $sped->destinatario_json : [];
            $nomeCorriere = trim((string) ($sped->corriere ?? ''));
            if ($nomeCorriere === '' && $sped->corriereRecord) {
                $nomeCorriere = trim((string) ($sped->corriereRecord->nome_visualizzato ?? ''))
                    ?: trim((string) ($sped->corriereRecord->nome_corriere ?? ''));
            }
            if ($nomeCorriere === '') {
                $nomeCorriere = 'Corriere';
            }
            $tracking = trim((string) ($sped->tracking ?? ''));
            $codice = trim((string) ($sped->codice_interno ?? ''));
        @endphp
        <div class="sq-card sq-card--p-14-16">
            <h2 class="sq-h2-brand">Spedizione trovata</h2>
            <p class="sq-m-0 sq-mb-10">
                <strong>Corriere:</strong> {{ e($nomeCorriere) }}
                @if ($codice !== '') · <strong>Codice:</strong> {{ e($codice) }} @endif
                @if ($tracking !== '') · <strong>Tracking:</strong> {{ e($tracking) }} @endif
            </p>
            <div class="sq-indirizzi-grid-2 sq-mb-12">
                <div>
                    <p class="sq-m-0 sq-text-13"><strong>Mittente originale</strong></p>
                    <p class="sq-m-0 sq-text-13">
                        {{ e(trim((string) ($mitt['nome'] ?? '') . ' ' . (string) ($mitt['cognome'] ?? ''))) }}
                        @if (!empty($mitt['indirizzo'])) — {{ e((string) $mitt['indirizzo']) }} @endif
                    </p>
                </div>
                <div>
                    <p class="sq-m-0 sq-text-13"><strong>Destinatario originale</strong></p>
                    <p class="sq-m-0 sq-text-13">
                        {{ e(trim((string) ($dest['nome'] ?? '') . ' ' . (string) ($dest['cognome'] ?? ''))) }}
                        @if (!empty($dest['indirizzo'])) — {{ e((string) $dest['indirizzo']) }} @endif
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('resi.crea', $sped) }}">
                @csrf
                <button type="submit" class="sq-btn-primary">Produci lettera di vettura per il reso</button>
            </form>
        </div>
    @endif
</div>
@endsection
