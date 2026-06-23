@php
    use App\Support\ServizioAggiuntivoEtichetta;

    $labels = [];
    foreach ($spedizione->serviziAggiuntiviRighe as $riga) {
        $lbl = ServizioAggiuntivoEtichetta::perRiga($riga);
        if ($lbl !== '') {
            $labels[] = $lbl;
        }
    }
@endphp
@if (count($labels) > 0)
    <span class="sq-text-14">{!! implode('<br>', array_map('e', $labels)) !!}</span>
@else
    <span class="sq-text-muted">—</span>
@endif
