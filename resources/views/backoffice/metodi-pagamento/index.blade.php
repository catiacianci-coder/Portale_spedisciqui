@extends('layouts.app')

@section('content')
@php
    $urlContesto = static fn (string $c) => route('backoffice.metodi_pagamento.index', ['contesto' => $c]);
@endphp

<div class="sq-listing-page sq-bo-metodi-pagamento-page ordini-index-page">
    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="sq-alert sq-alert--danger sq-mb-16">
            <ul class="sq-mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="sq-bo-metodi-pagamento-lead">
        Configura i metodi di pagamento per contesto. Il <strong>codice</strong> tecnico non è modificabile; usa nome, stato e commissioni.
    </p>

    <div class="sq-listing-tabs sq-bo-metodi-pagamento-tabs" role="tablist" aria-label="Contesto metodi di pagamento">
        @foreach ($contesti as $row)
            <a href="{{ $urlContesto($row['id']) }}"
               role="tab"
               aria-selected="{{ $contesto === $row['id'] ? 'true' : 'false' }}"
               class="sq-listing-tab-card @if ($contesto === $row['id']) is-active @endif">
                <div class="sq-listing-tab-card__title">{{ $row['label'] }}</div>
                <div class="sq-listing-tab-card__count">{{ $conteggi[$row['id']] ?? 0 }}</div>
                <div class="sq-listing-tab-card__sub">{{ $row['description'] }}</div>
            </a>
        @endforeach
    </div>

    <section class="sq-ordini-tab-section" role="tabpanel" aria-label="{{ \App\Support\BackofficeMetodiPagamentoConfig::labelContesto($contesto) }}">
        <div class="sq-table-wrap sq-table-wrap--warm sq-bo-metodi-pagamento-table-wrap">
            <table class="sq-table sq-bo-metodi-pagamento-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--warm">
                        <th class="sq-th sq-th--warm sq-bo-metodi-pagamento-col-codice">Codice</th>
                        <th class="sq-th sq-th--warm">Nome</th>
                        <th class="sq-th sq-th--warm sq-bo-metodi-pagamento-col-stato">Stato</th>
                        <th class="sq-th sq-th--warm sq-bo-metodi-pagamento-col-comm">Commissioni %</th>
                        <th class="sq-th sq-th--warm">Note</th>
                        <th class="sq-th sq-th--warm sq-bo-metodi-pagamento-col-azioni">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($metodi as $m)
                        @php
                            $formId = 'bo-metodo-pagamento-form-'.$contesto.'-'.$m->id;
                        @endphp
                        <tr>
                            <td class="sq-td sq-td--border-warm sq-td--top sq-bo-metodi-pagamento-col-codice">
                                <code class="sq-bo-metodi-pagamento-codice">{{ $m->codice }}</code>
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top">
                                <label class="sq-sr-only" for="{{ $formId }}-nome">Nome metodo</label>
                                <input
                                    type="text"
                                    id="{{ $formId }}-nome"
                                    name="metodo_pagamento"
                                    form="{{ $formId }}"
                                    value="{{ old('metodo_pagamento', $m->metodo_pagamento) }}"
                                    maxlength="120"
                                    required
                                    class="sq-bo-metodi-pagamento-input"
                                >
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top sq-bo-metodi-pagamento-col-stato">
                                @if ($m->abilitato)
                                    <span class="sq-bo-metodi-pagamento-pill sq-bo-metodi-pagamento-pill--ok">Attivo</span>
                                @else
                                    <span class="sq-bo-metodi-pagamento-pill">Off</span>
                                @endif
                                <form
                                    method="POST"
                                    action="{{ route('backoffice.metodi_pagamento.toggle', ['contesto' => $contesto, 'id' => $m->id]) }}"
                                    class="sq-form-zero sq-bo-metodi-pagamento-toggle-form"
                                >
                                    @csrf
                                    <button type="submit" class="sq-bo-metodi-pagamento-link-btn">
                                        {{ $m->abilitato ? 'Disabilita' : 'Abilita' }}
                                    </button>
                                </form>
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top sq-bo-metodi-pagamento-col-comm">
                                <label class="sq-sr-only" for="{{ $formId }}-comm">Commissioni</label>
                                <input
                                    type="number"
                                    id="{{ $formId }}-comm"
                                    name="commissioni"
                                    form="{{ $formId }}"
                                    value="{{ old('commissioni', $m->commissioni) }}"
                                    step="0.0001"
                                    min="-100"
                                    max="100"
                                    required
                                    class="sq-bo-metodi-pagamento-input sq-bo-metodi-pagamento-input--comm"
                                >
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top">
                                <label class="sq-sr-only" for="{{ $formId }}-varie">Note</label>
                                <textarea
                                    id="{{ $formId }}-varie"
                                    name="varie"
                                    form="{{ $formId }}"
                                    rows="2"
                                    maxlength="500"
                                    class="sq-bo-metodi-pagamento-textarea"
                                >{{ old('varie', $m->varie) }}</textarea>
                            </td>
                            <td class="sq-td sq-td--border-warm sq-td--top sq-bo-metodi-pagamento-col-azioni">
                                <form
                                    id="{{ $formId }}"
                                    method="POST"
                                    action="{{ route('backoffice.metodi_pagamento.update', ['contesto' => $contesto, 'id' => $m->id]) }}"
                                    class="sq-form-zero"
                                >
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="sq-bo-metodi-pagamento-save-btn">Salva</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sq-td sq-td--border-warm sq-text-muted">Nessun metodo configurato per questo contesto.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
