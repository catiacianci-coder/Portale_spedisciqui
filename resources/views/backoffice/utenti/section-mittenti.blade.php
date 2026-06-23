@extends('layouts.app')
@section('content')
<div class="sq-bo-utenti-page sq-bo-ana-page">
    <p class="sq-mb-16">
        <a href="{{ route('backoffice.utenti.index') }}" class="sq-link-back">← Torna all'elenco utenti</a>
    </p>

    @if (session('ok'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('ok') }}</div>
    @endif

    <h2 class="sq-bo-ana-title">Utente #{{ $user->id }} — Rubrica mittenti</h2>
    <p class="sq-text-muted sq-mb-16">
        {{ $user->displayNameForBackoffice() }} · {{ $user->email }}
    </p>
    <p class="sq-text-muted sq-mb-16">
        La flag <strong>Sede Liccardi</strong> è visibile solo in backoffice: il cliente non la vede nella propria rubrica.
    </p>

    @if ($mittenti->isEmpty())
        <div class="sq-alert sq-alert--info-warm">Nessun mittente in rubrica per questo utente.</div>
    @else
        <div class="sq-table-wrap">
            <table class="sq-table">
                <thead>
                    <tr class="sq-thead-row sq-thead-row--neutral">
                        <th class="sq-th">Riferimento</th>
                        <th class="sq-th">Indirizzo</th>
                        <th class="sq-th">Contatti</th>
                        <th class="sq-th">Badge</th>
                        <th class="sq-th">Sede Liccardi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mittenti as $m)
                        <tr>
                            <td class="sq-td">
                                <div class="sq-fw-700">{{ $m->denominazione_ragione_sociale ?: trim($m->nome.' '.$m->cognome) ?: '—' }}</div>
                                @if ($m->denominazione_ragione_sociale && trim($m->nome.$m->cognome) !== '')
                                    <div class="sq-text-muted sq-text-13">{{ trim($m->nome.' '.$m->cognome) }}</div>
                                @endif
                                <div class="sq-text-muted sq-text-12">#{{ $m->id }}</div>
                            </td>
                            <td class="sq-td sq-td--muted">
                                {{ $m->indirizzo }} {{ $m->civico }}<br>
                                {{ $m->cap }} {{ $m->citta }} ({{ $m->provincia }})
                            </td>
                            <td class="sq-td sq-td--muted sq-text-13">
                                {{ $m->telefono ?: '—' }}<br>
                                {{ $m->email ?: '—' }}
                            </td>
                            <td class="sq-td sq-text-13">
                                @if ($m->is_fatturazione)
                                    <span class="sq-badge sq-badge--muted">Fatturazione</span>
                                @endif
                                @if ($m->is_preferito)
                                    <span class="sq-badge sq-badge--muted">Preferito</span>
                                @endif
                                @if (! $m->is_fatturazione && ! $m->is_preferito)
                                    —
                                @endif
                            </td>
                            <td class="sq-td">
                                <form method="POST" action="{{ route('backoffice.utenti.mittenze.sede_liccardi.toggle', [$user, $m]) }}" class="sq-m-0">
                                    @csrf
                                    <button type="submit" class="sq-bo-btn-link {{ $m->sede_liccardi ? 'sq-bo-btn-green' : 'sq-bo-btn-gray' }}">
                                        {{ $m->sede_liccardi ? 'Sì — disattiva' : 'No — attiva' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
