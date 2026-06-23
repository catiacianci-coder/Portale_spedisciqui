@extends('layouts.app')

@section('content')
@php
    $urlVista = static fn (string $v) => route('backoffice.utilities.index', ['vista' => $v]);
    $fmtData = static function ($date): string {
        if ($date === null || $date === '') {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable) {
            return '—';
        }
    };
    $fmtDataInput = static function ($date): string {
        if ($date === null || $date === '') {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    };
    $dash = static fn ($v) => ($v === null || $v === '') ? '—' : $v;
@endphp

<div class="sq-listing-page sq-bo-utilities-page ordini-index-page">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->any() && $vista === 'parametri')
        <div class="sq-alert sq-alert--danger sq-mb-16">
            <ul class="sq-mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="sq-bo-utilities-lead">
        <a href="{{ route('backoffice.utilities.msg_tracciamento.index') }}" class="sq-header-link">Messaggi tracking</a>
        ·
        <a href="{{ route('backoffice.parametri_globali.edit') }}" class="sq-header-link">Parametri impresa / API</a>
    </p>

    <div class="sq-listing-tabs sq-bo-utilities-tabs" role="tablist" aria-label="Utilities">
        <a href="{{ $urlVista('parametri') }}"
           role="tab"
           aria-selected="{{ $vista === 'parametri' ? 'true' : 'false' }}"
           class="sq-listing-tab-card @if ($vista === 'parametri') is-active @endif">
            <div class="sq-listing-tab-card__title">Parametri globali</div>
            <div class="sq-listing-tab-card__count">{{ $parametriTotali ?? $parametri->count() }}</div>
            <div class="sq-listing-tab-card__sub">record in tabella</div>
        </a>
        <a href="{{ $urlVista('ricarichi') }}"
           role="tab"
           aria-selected="{{ $vista === 'ricarichi' ? 'true' : 'false' }}"
           class="sq-listing-tab-card @if ($vista === 'ricarichi') is-active @endif">
            <div class="sq-listing-tab-card__title">Ricarichi</div>
            <div class="sq-listing-tab-card__count">{{ $ricarichi->count() }}</div>
            <div class="sq-listing-tab-card__sub">profili percentuale</div>
        </a>
    </div>

    @if ($vista === 'parametri')
        <section class="sq-ordini-tab-section" role="tabpanel" aria-label="Parametri globali">
            <p class="sq-bo-utilities-hint">
                Tabella in sola lettura: <strong>Modifica</strong> per cambiare la riga, <strong>Duplica</strong> per creare una nuova versione con date diverse.
            </p>
            <details class="sq-bo-utilities-guida">
                <summary>Come funzionano le date di validità</summary>
                <div class="sq-bo-utilities-guida__body">
                    <p>Lo stesso parametro (es. Aliquota IVA) può avere <strong>più righe</strong>: ognuna vale in un periodo diverso.</p>
                    <ul>
                        <li><strong>Inizio validità</strong> — da quale giorno vale quella riga.</li>
                        <li><strong>Fine validità vuota</strong> — è l’ultima versione creata, senza data di chiusura.</li>
                        <li><strong>Fine validità compilata</strong> — quella versione termina in quel giorno e non si usa più dopo.</li>
                    </ul>
                    <p><strong>Quale riga usa il sito oggi?</strong></p>
                    <ol>
                        <li>Guarda l’ultima riga con fine validità vuota: se oggi è già passata la sua data di inizio, usa quella.</li>
                        <li>Se quella riga inizia in futuro, usa la riga precedente il cui periodo include ancora oggi.</li>
                    </ol>
                    <p class="sq-bo-utilities-guida__esempio">
                        <strong>Esempio:</strong> riga vecchia 22% fino al 31/05/2026, riga nuova 23% dal 01/06/2026 (fine vuota).
                        Il 15/03/2026 vale il 22%; dal 01/06/2026 vale il 23%.
                    </p>
                    <p><strong>Duplica:</strong> crea una nuova riga con i dati copiati (fine validità vuota di default, modificabile) e chiude la riga originale al giorno prima della nuova data di inizio.</p>
                </div>
            </details>
            @include('backoffice.utilities.partials.parametri-filtri')
            <div class="sq-table-wrap sq-table-wrap--warm sq-bo-utilities-table-wrap">
                <table class="sq-table sq-bo-utilities-table" id="sq-bo-util-parametri-table">
                    <thead>
                        <tr class="sq-thead-row sq-thead-row--warm">
                            <th class="sq-th sq-th--warm">ID</th>
                            <th class="sq-th sq-th--warm">Denominazione</th>
                            <th class="sq-th sq-th--warm">Val. assoluto</th>
                            <th class="sq-th sq-th--warm">Val. %</th>
                            <th class="sq-th sq-th--warm">Inizio validità</th>
                            <th class="sq-th sq-th--warm">Fine validità</th>
                            <th class="sq-th sq-th--warm">Metodo pagamento</th>
                            <th class="sq-th sq-th--warm">Valore testo</th>
                            <th class="sq-th sq-th--warm">Varie</th>
                            <th class="sq-th sq-th--warm sq-bo-utilities-col-azioni">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($parametri as $p)
                            @php
                                $formId = 'sq-bo-util-pg-form-'.$p->id;
                                $inizioInput = old('inizio_validita', $fmtDataInput($p->inizio_validita ?? '2026-04-01'));
                                $fineInput = old('fine_validita', $fmtDataInput($p->fine_validita));
                            @endphp
                            <tr class="sq-bo-util-row" data-util-row="{{ $p->id }}" data-util-table="parametri">
                                <td class="sq-td sq-td--border-warm sq-td--top">{{ $p->id }}</td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $p->denominazione }}</span>
                                    <input form="{{ $formId }}" type="text" name="denominazione" value="{{ old('denominazione', $p->denominazione) }}" maxlength="160" class="sq-bo-util-inp sq-bo-util-edit" required>
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $dash($p->valore_assoluto) }}</span>
                                    <input form="{{ $formId }}" type="number" step="0.0001" name="valore_assoluto" value="{{ old('valore_assoluto', $p->valore_assoluto) }}" class="sq-bo-util-inp sq-bo-util-edit">
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $dash($p->valore_percentuale) }}</span>
                                    <input form="{{ $formId }}" type="number" step="0.0001" name="valore_percentuale" value="{{ old('valore_percentuale', $p->valore_percentuale) }}" class="sq-bo-util-inp sq-bo-util-edit">
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $fmtData($p->inizio_validita) }}</span>
                                    <input form="{{ $formId }}" type="date" name="inizio_validita" value="{{ $inizioInput }}" class="sq-bo-util-inp sq-bo-util-edit">
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $fmtData($p->fine_validita) }}</span>
                                    <input form="{{ $formId }}" type="date" name="fine_validita" value="{{ $fineInput }}" class="sq-bo-util-inp sq-bo-util-edit">
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $p->metodoPagamento?->metodo_pagamento ?? '—' }}</span>
                                    <select form="{{ $formId }}" name="id_metodo_pagamentos" class="sq-bo-util-inp sq-bo-util-edit">
                                        <option value="">—</option>
                                        @foreach ($metodiPagamento as $mp)
                                            <option value="{{ $mp->id }}" @selected((int) old('id_metodo_pagamentos', $p->id_metodo_pagamentos) === (int) $mp->id)>
                                                {{ $mp->metodo_pagamento }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top sq-bo-util-cell-wide">
                                    <span class="sq-bo-util-read sq-bo-util-clip">{{ $dash($p->valore_testo) }}</span>
                                    <textarea form="{{ $formId }}" name="valore_testo" rows="2" class="sq-bo-util-inp sq-bo-util-edit sq-bo-util-inp--area">{{ old('valore_testo', $p->valore_testo) }}</textarea>
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top sq-bo-util-cell-wide">
                                    <span class="sq-bo-util-read sq-bo-util-clip">{{ $dash($p->varie) }}</span>
                                    <textarea form="{{ $formId }}" name="varie" rows="2" class="sq-bo-util-inp sq-bo-util-edit sq-bo-util-inp--area">{{ old('varie', $p->varie) }}</textarea>
                                </td>
                                @php
                                    $utilDuplicaPayload = base64_encode(json_encode([
                                        'denominazione' => $p->denominazione,
                                        'valore_assoluto' => $p->valore_assoluto,
                                        'valore_percentuale' => $p->valore_percentuale,
                                        'inizio_validita' => $fmtDataInput($p->inizio_validita ?? '2026-04-01'),
                                        'id_metodo_pagamentos' => $p->id_metodo_pagamentos,
                                        'valore_testo' => $p->valore_testo,
                                        'varie' => $p->varie,
                                    ], JSON_THROW_ON_ERROR));
                                @endphp
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <div class="sq-bo-utilities-actions">
                                        <button type="button" class="sq-bo-btn-link sq-bo-btn-blue js-util-modifica" data-row="{{ $p->id }}">Modifica</button>
                                        <button type="button"
                                                class="sq-bo-btn-link sq-bo-btn-wallet js-util-duplica"
                                                data-row="{{ $p->id }}"
                                                data-duplica-url="{{ route('backoffice.utilities.parametri_globali.duplica', $p) }}"
                                                data-payload-b64="{{ $utilDuplicaPayload }}">Duplica</button>
                                        <button type="submit" form="{{ $formId }}" class="sq-bo-btn-link sq-bo-btn-green js-util-salva" data-row="{{ $p->id }}" hidden>Salva</button>
                                        <button type="button" class="sq-bo-btn-link sq-bo-btn-gray js-util-annulla sq-bo-util-annulla-btn" data-row="{{ $p->id }}" hidden>Annulla</button>
                                    </div>
                                </td>
                            </tr>
                            <form id="{{ $formId }}" method="POST" action="{{ route('backoffice.utilities.parametri_globali.update', $p) }}" class="sq-bo-util-form-marker">
                                @csrf
                            </form>
                        @empty
                            <tr>
                                <td class="sq-td sq-td--border-warm" colspan="10">Nessun record trovato con i filtri selezionati.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="sq-ordini-tab-section" role="tabpanel" aria-label="Ricarichi">
            <div class="sq-table-wrap sq-table-wrap--warm sq-bo-utilities-table-wrap">
                <table class="sq-table sq-bo-utilities-table" id="sq-bo-util-ricarichi-table">
                    <thead>
                        <tr class="sq-thead-row sq-thead-row--warm">
                            <th class="sq-th sq-th--warm">ID</th>
                            <th class="sq-th sq-th--warm">Nome</th>
                            <th class="sq-th sq-th--warm">Percentuale</th>
                            <th class="sq-th sq-th--warm sq-bo-utilities-col-azioni">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ricarichi as $r)
                            @php $formId = 'sq-bo-util-rc-form-'.$r->id; @endphp
                            <tr class="sq-bo-util-row" data-util-row="{{ $r->id }}" data-util-table="ricarichi">
                                <td class="sq-td sq-td--border-warm sq-td--top">{{ $r->id }}</td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ $dash($r->nome) }}</span>
                                    <input form="{{ $formId }}" type="text" name="nome" value="{{ old('nome', $r->nome) }}" maxlength="255" class="sq-bo-util-inp sq-bo-util-edit">
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <span class="sq-bo-util-read">{{ number_format((float) $r->percentuale, 2, ',', '.') }}%</span>
                                    <input form="{{ $formId }}" type="number" step="0.01" min="0" name="percentuale" value="{{ old('percentuale', $r->percentuale) }}" class="sq-bo-util-inp sq-bo-util-edit" required>
                                </td>
                                <td class="sq-td sq-td--border-warm sq-td--top">
                                    <div class="sq-bo-utilities-actions">
                                        <button type="button" class="sq-bo-btn-link sq-bo-btn-blue js-util-modifica" data-row="{{ $r->id }}">Modifica</button>
                                        <button type="submit" form="{{ $formId }}" class="sq-bo-btn-link sq-bo-btn-green js-util-salva" data-row="{{ $r->id }}" hidden>Salva</button>
                                        <button type="button" class="sq-bo-btn-link sq-bo-btn-gray js-util-annulla sq-bo-util-annulla-btn" data-row="{{ $r->id }}" hidden>Annulla</button>
                                    </div>
                                </td>
                            </tr>
                            <form id="{{ $formId }}" method="POST" action="{{ route('backoffice.utilities.ricarichi.update', $r) }}" class="sq-bo-util-form-marker">
                                @csrf
                            </form>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

@if ($vista === 'parametri')
@include('backoffice.utilities.partials.duplica-modal')
@include('backoffice.utilities.partials.nuovo-modal')
@endif
@include('backoffice.utilities.partials.row-edit-script')
@endsection
