@php
    $stato = $stato ?? '';
    $tipo = $tipo ?? 'ordine';
@endphp
@switch($stato)
    @case('pagato')
    @case('accreditata')
        <span class="sq-stato-tabella sq-stato-tabella--ok" title="Pagato">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>Pagato</span>
        </span>
        @break
    @case('non_pagato')
    @case('in_attesa')
        <span class="sq-stato-tabella sq-stato-tabella--pending" title="Non pagato">
            <i class="fa-solid fa-clock" aria-hidden="true"></i>
            <span>Non pagato</span>
        </span>
        @break
    @case('rimborsato')
        <span class="sq-stato-tabella sq-stato-tabella--ok" title="Rimborsato su Stripe">
            <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            <span>Rimborsato</span>
        </span>
        @break
    @case('rimborsata')
        <span class="sq-stato-tabella sq-stato-tabella--ok" title="{{ $label ?? 'Rimborsata' }}">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span>{{ $label ?? 'Rimborsata' }}</span>
        </span>
        @break
    @case('in_attesa_rimborso')
        <span class="sq-stato-tabella sq-stato-tabella--pending" title="In attesa di rimborso">
            <i class="fa-solid fa-clock" aria-hidden="true"></i>
            <span>In attesa di rimborso</span>
        </span>
        @break
    @case('annullato')
    @case('annullata')
    @case('cancellato')
        <span class="sq-stato-tabella sq-stato-tabella--cancel" title="Cancellato">
            <i class="fa-solid fa-ban" aria-hidden="true"></i>
            <span>Cancellato</span>
        </span>
        @break
    @default
        <span class="sq-stato-tabella sq-stato-tabella--muted">
            <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
            <span>{{ $stato !== '' ? $stato : '—' }}</span>
        </span>
@endswitch
