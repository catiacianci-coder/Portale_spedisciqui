@php
    $it = $it ?? [];
    $spedCardWhiteBg = ! empty($spedCardWhiteBg);
    $spedCardCompact = ! empty($spedCardCompact);
    $cid = (int) ($it['corriere_id'] ?? 0);
    $logo = $it['logo_url'] ?? \App\Support\CorriereLogo::pubblico($cid);
    $nomeVis = trim((string) ($it['corriere_nome_visualizzato'] ?? $it['corriere_nome'] ?? '')) ?: 'Spedizione';
    $spedCardPaymentSummaryTableRow = ! empty($spedCardPaymentSummaryTableRow);
    $spedCardPaymentSummaryTemplate = ! empty($spedCardPaymentSummaryTemplate);
    $spedCardPaymentSummaryId = trim((string) ($spedCardPaymentSummaryId ?? ''));
    $iniziale = mb_strtoupper(mb_substr($nomeVis, 0, 1));
    $ind = $it['indirizzi'] ?? [];
    $part = is_array($ind['partenza'] ?? null) ? $ind['partenza'] : [];
    $dest = is_array($ind['destinazione'] ?? null) ? $ind['destinazione'] : [];
    $capDa = trim((string) ($part['cap'] ?? ''));
    $capA = trim((string) ($dest['cap'] ?? ''));
    $cittaMitt = trim((string) ($part['comune'] ?? ''));
    $provMitt = strtoupper(substr(trim((string) ($part['provincia'] ?? '')), 0, 2));
    $cittaDest = trim((string) ($dest['comune'] ?? ''));
    $provDest = strtoupper(substr(trim((string) ($dest['provincia'] ?? '')), 0, 2));
    $geoMitt = trim($capDa.' '.$cittaMitt.($provMitt !== '' ? ' ('.$provMitt.')' : ''));
    $geoDest = trim($capA.' '.$cittaDest.($provDest !== '' ? ' ('.$provDest.')' : ''));
    $codiceInternoSped = trim((string) ($it['codice_interno_spedizione'] ?? ''));
    $tipoSpedNome = trim((string) ($it['tipo_spedizione_nome'] ?? ''));
    $nomeMitt = trim((string) (($part['nome'] ?? '') . ' ' . ($part['cognome'] ?? '')));
    $indMitt = trim((string) ($part['indirizzo'] ?? ''));
    if ($indMitt === '') {
        $indMitt = trim((string) (($part['via'] ?? '') . ' ' . ($part['numero'] ?? '')));
    }
    $noteMitt = trim((string) ($part['note'] ?? ''));
    $nomeDest = trim((string) (($dest['nome'] ?? '') . ' ' . ($dest['cognome'] ?? '')));
    if ($nomeDest === '') {
        $nomeDest = trim((string) ($dest['nome_destinatario'] ?? ''));
    }
    if ($nomeDest === '') {
        $nomeDest = trim((string) ($dest['nome_cognome'] ?? ''));
    }
    if ($nomeDest === '') {
        $nomeDest = trim((string) ($dest['ragione_sociale'] ?? ''));
    }
    $indDest = trim((string) ($dest['indirizzo'] ?? ''));
    if ($indDest === '') {
        $indDest = trim((string) (($dest['via'] ?? '') . ' ' . ($dest['numero'] ?? '')));
    }
    $noteDest = trim((string) ($dest['note'] ?? ''));
    $rawPacco = $it['dati_pacco'] ?? null;
    if (is_string($rawPacco)) {
        $dec = json_decode($rawPacco, true);
        $pacco = is_array($dec) ? $dec : [];
    } else {
        $pacco = is_array($rawPacco) ? $rawPacco : [];
    }
    $peso = $pacco['peso_kg'] ?? null;
    $h = $pacco['altezza_cm'] ?? null;
    $w = $pacco['larghezza_cm'] ?? null;
    $d = $pacco['spessore_cm'] ?? null;
    $fmtDim = ($h !== null && $w !== null && $d !== null)
        ? number_format((float) $h, 2, ',', '.') . ' × ' . number_format((float) $w, 2, ',', '.') . ' × ' . number_format((float) $d, 2, ',', '.') . ' cm'
        : null;
    $serviziSel = $it['servizi_selezionati'] ?? [];
    $etichetteServizi = [];
    foreach (is_array($serviziSel) ? $serviziSel : [] as $sx) {
        if (! is_array($sx)) {
            continue;
        }
        $pid = isset($sx['id']) ? (int) $sx['id'] : 0;
        $pivot = $pid ? \App\Models\corrieri_servizi_aggiuntivi::query()->find($pid) : null;
        $label = $pivot?->testo_servizio ?? ($pid ? 'Servizio aggiuntivo' : 'Servizio');
        $valoreMerce = isset($sx['valore_merce']) && $sx['valore_merce'] !== null && $sx['valore_merce'] !== ''
            ? (float) $sx['valore_merce']
            : null;
        if ($valoreMerce !== null && $valoreMerce > 0) {
            $label .= ' ('.number_format($valoreMerce, 2, ',', '.').' €)';
        }
        $etichetteServizi[] = $label;
    }
@endphp
@if ($spedCardPaymentSummaryTableRow && $spedCardPaymentSummaryId !== '')
    <tr>
        <td class="sq-td sq-fw-700">{{ e($nomeVis) }}</td>
        <td class="sq-td sq-td--muted">{{ $capDa !== '' ? e($capDa) : '—' }}</td>
        <td class="sq-td sq-td--muted">{{ $capA !== '' ? e($capA) : '—' }}</td>
        <td class="sq-td sq-td--right">
            <div class="sq-ordini-actions-icons sq-ordini-actions-icons--table-cell">
                <button
                    type="button"
                    class="sq-ordini-icon-action sq-ordini-icon-action--view js-ordine-sped-detail-open"
                    title="Dettaglio spedizione"
                    aria-label="Dettaglio spedizione {{ e($nomeVis) }}"
                    aria-haspopup="dialog"
                    aria-controls="sq-ordine-sped-modal"
                    data-sped-detail-template="{{ e($spedCardPaymentSummaryId) }}"
                >
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            </div>
        </td>
    </tr>
@elseif ($spedCardPaymentSummaryTemplate && $spedCardPaymentSummaryId !== '')
    <template id="{{ e($spedCardPaymentSummaryId) }}">
        <div class="sq-sped-pay-summary-modal-inner">
            @include('partials.spedizione-card-compact-body')
        </div>
    </template>
@else
<div class="sq-sped-card @if($spedCardWhiteBg) sq-sped-card--white-bg @endif @if($spedCardCompact) sq-sped-card--compact @endif">
    <div class="sq-sped-body">
        @if ($spedCardCompact)
            @include('partials.spedizione-card-compact-body')
        @else
            <div class="sq-sped-title">{{ $nomeVis }}</div>
            <div class="sq-sped-line-muted">
                <strong class="sq-sped-strong">CAP invio</strong> {{ $capDa !== '' ? e($capDa) : '—' }}
                <span class="sq-sped-sep">|</span>
                <strong class="sq-sped-strong">CAP destinazione</strong> {{ $capA !== '' ? e($capA) : '—' }}
            </div>
            <div class="sq-sped-dest">
                <strong>Mittente:</strong> {{ $nomeMitt !== '' ? e($nomeMitt) : '—' }}
                @if ($indMitt !== '')
                    <span class="sq-sped-sep">·</span>
                    {{ e($indMitt) }}
                @endif
            </div>
            @if ($noteMitt !== '')
                <div class="sq-sped-note sq-text-muted sq-text-14">Note ritiro: {{ e($noteMitt) }}</div>
            @endif
            <div class="sq-sped-dest">
                <strong>Destinatario:</strong> {{ $nomeDest !== '' ? e($nomeDest) : '—' }}
                @if ($indDest !== '')
                    <span class="sq-sped-sep">·</span>
                    {{ e($indDest) }}
                @endif
            </div>
            @if ($noteDest !== '')
                <div class="sq-sped-note sq-text-muted sq-text-14">Note consegna: {{ e($noteDest) }}</div>
            @endif
            <div class="sq-sped-pacco">
                @if ($peso !== null)
                    <strong class="sq-sped-strong">Peso:</strong> {{ number_format((float) $peso, 2, ',', '.') }} kg
                @else
                    <strong class="sq-sped-strong">Peso:</strong> —
                @endif
                @if ($fmtDim)
                    <span class="sq-sped-sep">·</span>
                    <strong class="sq-sped-strong">Misure (H×L×P):</strong> {{ $fmtDim }}
                @else
                    <span class="sq-sped-sep">·</span>
                    <strong class="sq-sped-strong">Misure:</strong> —
                @endif
            </div>
            <div class="sq-sped-servizi-title">
                <strong class="sq-text-heading">Servizi aggiuntivi</strong>
                @if (count($etichetteServizi) > 0)
                    <ul class="sq-sped-servizi-list">
                        @foreach ($etichetteServizi as $label)
                            <li>{{ e($label) }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="sq-sped-servizi-empty">Nessun servizio aggiuntivo selezionato.</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endif
