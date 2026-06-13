@php
    $labels = [];
    foreach ($spedizione->serviziAggiuntiviRighe as $riga) {
        $lbl = $riga->denominazione_servizio
            ?? $riga->corriereServizioAggiuntivo?->testo_servizio;
        if ($lbl) {
            $val = isset($riga->valore_merce) && $riga->valore_merce !== null
                ? (float) $riga->valore_merce
                : null;
            if ($val !== null && $val > 0) {
                $lbl .= ' ('.number_format($val, 2, ',', '.').' €)';
            }
            $labels[] = $lbl;
        }
    }
@endphp
@if (count($labels) > 0)
    <span class="sq-text-14">{{ implode(', ', array_map('e', $labels)) }}</span>
@else
    <span class="sq-text-muted">—</span>
@endif
