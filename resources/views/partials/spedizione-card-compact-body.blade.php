{{-- Dettaglio card compatta: prima riga tecnica, seconda riga indirizzi. Codice spedizione solo se già assegnato (es. non in carrello prima dell’ordine). --}}
<div class="sq-sped-line-compact sq-sped-line-muted">
    @if ($codiceInternoSped !== '')
        <span><strong class="sq-sped-strong">Codice Spedizione:</strong> {{ e($codiceInternoSped) }}</span>
    @endif
    <span><strong class="sq-sped-strong">Corriere:</strong> {{ e($nomeVis) }}</span>
    @if ($tipoSpedNome !== '')
        <span><strong class="sq-sped-strong">{{ e($tipoSpedNome) }}:</strong></span>
    @endif
    <span>{{ $peso !== null ? number_format((float) $peso, 2, ',', '.') . ' kg' : '—' }}</span>
    <span>{{ $fmtDim ?: '—' }}</span>
    @if (count($etichetteServizi) > 0)
        <span><strong class="sq-sped-strong">Servizi aggiuntivi:</strong></span>
        <ul class="sq-sped-servizi-list sq-sped-servizi-list--inline">
            @foreach ($etichetteServizi as $label)
                <li>{{ e($label) }}</li>
            @endforeach
        </ul>
    @endif
</div>

<div class="sq-sped-address-compact-row">
    <div class="sq-sped-address-compact-col">
        <strong class="sq-sped-strong">Mittente:</strong>
        <span>
            {{ $nomeMitt !== '' ? e($nomeMitt) : '—' }}
            @if ($indMitt !== '')
                - {{ e($indMitt) }}
            @endif
            @if ($geoMitt !== '')
                {{ ' ' . e($geoMitt) }}
            @endif
        </span>
    </div>
    <div class="sq-sped-address-compact-col">
        <strong class="sq-sped-strong">Destinatario:</strong>
        <span>
            {{ $nomeDest !== '' ? e($nomeDest) : '—' }}
            @if ($indDest !== '')
                - {{ e($indDest) }}
            @endif
            @if ($geoDest !== '')
                {{ ' ' . e($geoDest) }}
            @endif
        </span>
    </div>
</div>
