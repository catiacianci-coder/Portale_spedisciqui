@extends('layouts.app')
@section('content')
@php
    $cartaoTitulos = [
        'novo' => 'Nuovi',
        'aberto' => 'Aperti',
        'em_espera' => 'In attesa (cliente)',
        'em_tratamento' => 'In lavorazione (team)',
        'resolvido' => 'Risolti',
    ];
    $cartaoIcones = [
        'novo' => 'fa-inbox',
        'aberto' => 'fa-folder-open',
        'em_espera' => 'fa-user-clock',
        'em_tratamento' => 'fa-screwdriver-wrench',
        'resolvido' => 'fa-circle-check',
    ];
@endphp
<div class="sq-bo-page-wrap assistenza-bo-page">
    @if (session('status'))
        <div class="sq-alert sq-alert--success sq-mb-16">{{ session('status') }}</div>
    @endif

    <div class="assistenza-bo-cards">
        <a href="{{ route('backoffice.tickets.index') }}" class="assistenza-bo-card assistenza-bo-card--todos {{ $filter === null || $filter === '' ? 'is-active' : '' }}">
            <div class="assistenza-bo-card__title"><i class="fa-solid fa-list-ul" aria-hidden="true"></i> Tutti</div>
            <div class="assistenza-bo-card__count">{{ array_sum($counts) }}</div>
        </a>
        @foreach ($stati as $stato)
            <a href="{{ route('backoffice.tickets.index', ['stato' => $stato->codigo]) }}" class="assistenza-bo-card assistenza-bo-card--st-{{ $stato->codigo }} {{ $filter === $stato->codigo ? 'is-active' : '' }}">
                <div class="assistenza-bo-card__title">
                    <i class="fa-solid {{ $cartaoIcones[$stato->codigo] ?? 'fa-ticket' }}" aria-hidden="true"></i>
                    {{ $cartaoTitulos[$stato->codigo] ?? $stato->nome }}
                </div>
                <div class="assistenza-bo-card__count">{{ $counts[$stato->codigo] ?? 0 }}</div>
            </a>
        @endforeach
    </div>

    @include('partials.tabella-paginazione', [
        'paginator' => $tickets,
        'perPage' => $perPage,
        'queryParams' => request()->except('page'),
    ])

    <p class="assistenza-bo-filter-hint">
        @if ($filter)
            Visualizzazione ticket in «{{ $cartaoTitulos[$filter] ?? $filter }}».
        @else
            Visualizzazione di tutti i ticket.
        @endif
    </p>

    <div class="sq-table-wrap">
        <table class="sq-table">
            <thead>
                <tr class="sq-thead-row">
                    <th>#</th>
                    <th>Utente</th>
                    <th>Oggetto</th>
                    <th>Stato</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>
                            <a href="{{ route('backoffice.tickets.show', $ticket) }}{{ request()->filled('stato') ? '?stato='.urlencode((string) request('stato')) : '' }}">{{ $ticket->id }}</a>
                        </td>
                        <td>{{ $ticket->user?->name ?? '—' }}<br><span class="sq-text-muted" style="font-size:12px;">{{ $ticket->user?->email }}</span></td>
                        <td>{{ Str::limit($ticket->oggetto, 80) }}</td>
                        <td><span class="assistenza-pill">{{ $ticket->stato?->nome ?? '—' }}</span></td>
                        <td>{{ $ticket->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="sq-text-muted" style="text-align:center;padding:28px;">Nessun ticket in questo filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
