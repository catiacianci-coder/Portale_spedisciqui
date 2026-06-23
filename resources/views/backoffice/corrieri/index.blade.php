@extends('layouts.app')

@section('content')
@php
    use App\Support\CorriereBackofficeConfig;

    $urlVista = static fn (string $v, array $extra = []) => route('backoffice.corrieri.index', array_merge(['vista' => $v], $extra));
    $countCorrieri = $corrieri->count();
    $countCampi = count($campi);
@endphp

<div class="sq-listing-page sq-bo-corrieri-page ordini-index-page">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    <div class="sq-listing-tabs sq-bo-corrieri-tabs" role="tablist" aria-label="Modalità gestione corrieri">
        <a href="{{ $urlVista('corrieri') }}"
           role="tab"
           aria-selected="{{ $vista === 'corrieri' ? 'true' : 'false' }}"
           class="sq-listing-tab-card @if ($vista === 'corrieri') is-active @endif">
            <div class="sq-listing-tab-card__title">Per corriere</div>
            <div class="sq-listing-tab-card__count">{{ $countCorrieri }}</div>
            <div class="sq-listing-tab-card__sub">servizi attivi in anagrafica</div>
        </a>
        <a href="{{ $urlVista('campi', $openCampo !== '' ? ['campo' => $openCampo] : []) }}"
           role="tab"
           aria-selected="{{ $vista === 'campi' ? 'true' : 'false' }}"
           class="sq-listing-tab-card @if ($vista === 'campi') is-active @endif">
            <div class="sq-listing-tab-card__title">Per tipo di informazione</div>
            <div class="sq-listing-tab-card__count">{{ $countCampi }}</div>
            <div class="sq-listing-tab-card__sub">campi modificabili</div>
        </a>
    </div>

    @if ($vista === 'corrieri')
        <section class="sq-ordini-tab-section" role="tabpanel" aria-label="Gestione per corriere">
            <div class="sq-table-wrap sq-table-wrap--warm">
                <table class="sq-table sq-bo-corrieri-table">
                    <thead>
                        <tr class="sq-thead-row sq-thead-row--warm">
                            <th class="sq-th sq-th--warm sq-bo-corrieri-col-id">ID</th>
                            <th class="sq-th sq-th--warm">Servizio</th>
                            <th class="sq-th sq-th--warm">Piattaforma</th>
                            <th class="sq-th sq-th--warm">Stato</th>
                            <th class="sq-th sq-th--warm">Carosello</th>
                            <th class="sq-th sq-th--warm sq-bo-corrieri-col-azioni">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($corrieri as $c)
                            @php
                                $label = CorriereBackofficeConfig::labelCorriere($c);
                                $inCarosello = (int) $c->ord_carosello > 0;
                                $isOpen = $openCorriereId === (int) $c->id;
                            @endphp
                            <tr id="corriere-{{ $c->id }}" class="@if ($isOpen) sq-bo-corrieri-row--open @endif">
                                <td class="sq-td sq-td--border-warm sq-td--top sq-bo-corrieri-col-id">{{ $c->id }}</td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <strong>{{ $label }}</strong>
                                    @if ($c->nome_corriere_preventivo)
                                        <div class="sq-bo-corrieri-sub">{{ $c->nome_corriere_preventivo }}</div>
                                    @endif
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top sq-bo-corrieri-muted">{{ $c->piattaforma ?: '—' }}</td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    @if ($c->attivo)
                                        <span class="sq-bo-corrieri-pill sq-bo-corrieri-pill--ok">Attivo</span>
                                    @else
                                        <span class="sq-bo-corrieri-pill">Off</span>
                                    @endif
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    @if ($inCarosello)
                                        <span class="sq-bo-corrieri-pill sq-bo-corrieri-pill--car">Sì · {{ $c->ord_carosello }}</span>
                                    @else
                                        <span class="sq-bo-corrieri-pill">No</span>
                                    @endif
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top sq-bo-corrieri-col-azioni">
                                    <div class="sq-bo-corrieri-actions">
                                        <form method="POST" action="{{ route('backoffice.corrieri.attivo.toggle', $c) }}" class="sq-bo-corrieri-action-form">
                                            @csrf
                                            <button type="submit"
                                                    class="sq-bo-btn-link {{ $c->attivo ? 'sq-bo-btn-green' : 'sq-bo-btn-red' }}"
                                                    title="{{ $c->attivo ? 'Clic per disabilitare' : 'Clic per abilitare' }}">
                                                {{ $c->attivo ? 'Abilitato' : 'Disabilitato' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('backoffice.corrieri.carosello.toggle', $c) }}" class="sq-bo-corrieri-action-form">
                                            @csrf
                                            <button type="submit"
                                                    class="sq-bo-btn-link {{ $inCarosello ? 'sq-bo-btn-green' : 'sq-bo-btn-red' }}"
                                                    title="{{ $inCarosello ? 'Clic per rimuovere dal carosello' : 'Clic per aggiungere al carosello' }}">
                                                Carosello {{ $inCarosello ? 'sì' : 'no' }}
                                            </button>
                                        </form>
                                        <a href="{{ $isOpen ? $urlVista('corrieri') : $urlVista('corrieri', ['corriere' => $c->id]) }}"
                                           class="sq-bo-btn-link sq-bo-btn-blue">
                                            {{ $isOpen ? 'Chiudi' : 'Visualizza / modifica' }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @foreach ($corrieri as $c)
                @if ($openCorriereId === (int) $c->id)
                    <div class="sq-bo-corrieri-edit-box" id="sq-bo-corrieri-detail-{{ $c->id }}">
                        <div class="sq-bo-corrieri-edit-head">
                            <h3 class="sq-bo-corrieri-edit-title">
                                {{ CorriereBackofficeConfig::labelCorriere($c) }}
                                <span class="sq-bo-corrieri-edit-id">#{{ $c->id }}</span>
                            </h3>
                            <a href="{{ $urlVista('corrieri') }}" class="sq-bo-corrieri-btn sq-bo-corrieri-btn--ghost">Chiudi</a>
                        </div>
                        <form method="POST" action="{{ route('backoffice.corrieri.update', $c) }}" class="sq-bo-corrieri-edit-form">
                            @csrf
                            <input type="hidden" name="vista" value="corrieri">
                            <div class="sq-bo-corrieri-kv-list">
                                @foreach ($campi as $campoKey => $meta)
                                    @include('backoffice.corrieri.partials.riga-campo', [
                                        'corriere' => $c,
                                        'campoKey' => $campoKey,
                                        'meta' => $meta,
                                        'tipoOdOptions' => $tipoOdOptions,
                                        'ricarichi' => $ricarichi,
                                        'inputId' => 'corriere-'.$c->id.'-'.$campoKey,
                                    ])
                                @endforeach
                            </div>
                            <div class="sq-bo-corrieri-form-actions">
                                <button type="submit" class="sq-btn-primary sq-btn-sm">Salva modifiche</button>
                                <a href="{{ $urlVista('corrieri') }}" class="sq-btn-secondary sq-btn-sm">Annulla</a>
                            </div>
                        </form>
                    </div>
                @endif
            @endforeach
        </section>
    @else
        <section class="sq-ordini-tab-section" role="tabpanel" aria-label="Gestione per campo">
            <p class="sq-bo-corrieri-section-lead">Seleziona un campo, modifica i valori per ogni corriere e salva.</p>

            <div class="sq-bo-corrieri-field-toolbar" role="tablist" aria-label="Campi corrieri">
                @foreach ($campi as $campoKey => $meta)
                    <a href="{{ $urlVista('campi', ['campo' => $campoKey]) }}"
                       role="tab"
                       aria-selected="{{ $openCampo === $campoKey ? 'true' : 'false' }}"
                       class="sq-bo-corrieri-field-chip @if ($openCampo === $campoKey) is-active @endif">
                        {{ $meta['label'] }}
                    </a>
                @endforeach
            </div>

            @if ($openCampo === '' || ! isset($campi[$openCampo]))
                <div class="sq-bo-corrieri-empty">
                    Scegli un campo dai pulsanti sopra per visualizzare e modificare i valori.
                </div>
            @else
                @php $meta = $campi[$openCampo]; @endphp
                <div class="sq-bo-corrieri-edit-box">
                    <div class="sq-bo-corrieri-edit-head">
                        <h3 class="sq-bo-corrieri-edit-title">Campo: <code>{{ $openCampo }}</code></h3>
                    </div>
                    <form method="POST" action="{{ route('backoffice.corrieri.update_campo', $openCampo) }}">
                        @csrf
                        <input type="hidden" name="vista" value="campi">
                        @if (!empty($meta['hint']))
                            <p class="sq-bo-corrieri-section-lead sq-bo-corrieri-section-lead--inbox">{{ $meta['hint'] }}</p>
                        @endif
                        <div class="sq-bo-corrieri-kv-list sq-bo-corrieri-kv-list--bulk">
                            @foreach ($corrieri as $c)
                                <div class="sq-bo-corrieri-kv-row sq-bo-corrieri-kv-row--bulk">
                                    <div class="sq-bo-corrieri-kv-label sq-bo-corrieri-kv-label--corriere">
                                        <span class="sq-bo-corrieri-kv-corriere-id">#{{ $c->id }}</span>
                                        {{ CorriereBackofficeConfig::labelCorriere($c) }}
                                    </div>
                                    <div class="sq-bo-corrieri-kv-value">
                                        @include('backoffice.corrieri.partials.input-campo', [
                                            'corriere' => $c,
                                            'campoKey' => $openCampo,
                                            'meta' => $meta,
                                            'tipoOdOptions' => $tipoOdOptions,
                                            'ricarichi' => $ricarichi,
                                            'name' => 'values['.$c->id.']',
                                            'idPrefix' => 'bulk-'.$openCampo,
                                        ])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="sq-bo-corrieri-form-actions">
                            <button type="submit" class="sq-btn-primary sq-btn-sm">Salva «{{ $meta['label'] }}»</button>
                        </div>
                    </form>
                </div>
            @endif
        </section>
    @endif
</div>

@if ($openCorriereId > 0 && $vista === 'corrieri')
<script>
(() => {
    const box = document.getElementById('sq-bo-corrieri-detail-{{ $openCorriereId }}');
    if (box) {
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
@endif

<style>
.sq-bo-corrieri-page .sq-bo-corrieri-col-azioni {
    width: 1%;
    white-space: nowrap;
}
.sq-bo-corrieri-page .sq-bo-corrieri-actions {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 6px;
}
.sq-bo-corrieri-page .sq-bo-corrieri-action-form {
    margin: 0;
    display: inline-flex;
    flex: 0 0 auto;
}
.sq-bo-corrieri-page .sq-bo-corrieri-actions .sq-bo-btn-link {
    width: auto;
    min-width: 0;
    white-space: nowrap;
    padding: 4px 10px;
    font-size: 11px;
    line-height: 1.2;
    text-decoration: none;
}
</style>
@endsection
