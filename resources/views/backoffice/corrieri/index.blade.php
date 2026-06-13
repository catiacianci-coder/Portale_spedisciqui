@extends('layouts.app')

@section('content')
<div class="sq-page-1100 backoffice-corrieri">
    <p class="sq-intro">
        Gestione delle diciture <strong>punto ritiro</strong> e <strong>punto consegna</strong> mostrate nei preventivi e nel checkout.
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-18">{{ session('ok') }}</div>
    @endif

    <div class="sq-table-wrap sq-table-wrap--warm">
        <table class="sq-table">
            <thead>
                <tr class="sq-thead-row sq-thead-row--warm">
                    <th class="sq-th sq-th--warm">ID</th>
                    <th class="sq-th sq-th--warm">Servizio</th>
                    <th class="sq-th sq-th--warm">Ritiro / Consegna</th>
                    <th class="sq-th sq-th--warm">Punto ritiro</th>
                    <th class="sq-th sq-th--warm">Punto consegna</th>
                    <th class="sq-th sq-th--warm">Stato</th>
                    <th class="sq-th sq-th--warm">Azione</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($corrieri as $c)
                    <tr>
                        <td class="sq-td sq-td--border-warm sq-td--top">{{ $c->id }}</td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            <strong>{{ $c->nome_visualizzato ?: $c->nome_corriere }}</strong>
                            @if ($c->nome_corriere_preventivo)
                                <br><span class="sq-text-muted sq-font-sm">{{ $c->nome_corriere_preventivo }}</span>
                            @endif
                            @if ($c->piattaforma)
                                <br><span class="sq-text-muted sq-font-sm">{{ $c->piattaforma }}</span>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            {{ $c->pickup ?: '—' }}<br>
                            <span class="sq-text-muted">→ {{ $c->consegna ?: '—' }}</span>
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            {{ $c->punto_ritiro ?: '—' }}
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            {{ $c->punto_consegna ?: '—' }}
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            @if ($c->attivo)
                                <span class="sq-text-success">Attivo</span>
                            @else
                                <span class="sq-text-muted">Disattivo</span>
                            @endif
                        </td>
                        <td class="sq-td sq-td--border-warm sq-td--top">
                            <a href="{{ route('backoffice.corrieri.edit', $c) }}" class="sq-header-link">Modifica</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
