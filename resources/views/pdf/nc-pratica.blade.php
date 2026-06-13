<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>{{ $pratica->numero_pratica }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 6px; }
        .head-muted { color: #555; font-size: 9px; margin: 0 0 14px; }
        .blocco { border: 1px solid #333; margin-bottom: 14px; page-break-inside: avoid; }
        .blocco-h { font-weight: bold; font-size: 11px; padding: 6px 8px; background: #f0f0f0; border-bottom: 1px solid #333; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { width: 50%; vertical-align: top; padding: 8px; border-bottom: 1px solid #ccc; }
        table.layout td:first-child { border-right: 1px solid #ccc; }
        .col-title { font-weight: bold; margin-bottom: 6px; font-size: 10px; }
        .riga-dato { margin: 2px 0; }
        table.importi { width: 100%; border-collapse: collapse; margin-top: 0; }
        table.importi th, table.importi td { border: 1px solid #333; padding: 5px 6px; text-align: right; }
        table.importi th { background: #eee; text-align: center; font-size: 9px; }
        .corriere-pie { font-size: 9px; color: #555; padding: 4px 8px 6px; }
        .muted { color: #555; font-size: 9px; margin-top: 12px; }
    </style>
</head>
<body>
@php
    $fmtCm = static function (?float $v): string {
        if ($v === null) {
            return '—';
        }

        return number_format($v, 2, ',', '.');
    };
    $fmtKg = static function (?float $v): string {
        if ($v === null) {
            return '—';
        }

        return number_format($v, 3, ',', '.');
    };
    $fmtEur = static function (float $v): string {
        return number_format($v, 2, ',', '.');
    };
@endphp

    <h1>Pratica non conformità {{ $pratica->numero_pratica }}</h1>
    <p class="head-muted">Cliente: {{ $pratica->user?->email ?? '—' }} — Emissione pratica: {{ $pratica->created_at?->format('d/m/Y H:i') ?? '—' }}</p>

    @foreach ($pratica->righe as $r)
        @php
            $dataCliente = $r->spedizione?->created_at;
            $dataCorriere = $r->created_at;
        @endphp
        <div class="blocco">
            <div class="blocco-h">Codice interno: {{ $r->codice_interno }}</div>
            <table class="layout" cellspacing="0">
                <tr>
                    <td>
                        <div class="col-title">Dichiarato dal cliente</div>
                        <div class="riga-dato"><strong>Data</strong> {{ $dataCliente?->format('d/m/Y') ?? '—' }}</div>
                        <div class="riga-dato"><strong>Altezza</strong> {{ $fmtCm($r->altezza_dich) }} cm</div>
                        <div class="riga-dato"><strong>Larghezza</strong> {{ $fmtCm($r->larghezza_dich) }} cm</div>
                        <div class="riga-dato"><strong>Spessore</strong> {{ $fmtCm($r->spessore_dich) }} cm</div>
                        <div class="riga-dato"><strong>Peso</strong> {{ $fmtKg($r->peso_dich) }} kg</div>
                    </td>
                    <td>
                        <div class="col-title">Rilevato dal corriere</div>
                        <div class="riga-dato"><strong>Data</strong> {{ $dataCorriere?->format('d/m/Y') ?? '—' }}</div>
                        <div class="riga-dato"><strong>Altezza</strong> {{ $fmtCm($r->altezza_corriere) }} cm</div>
                        <div class="riga-dato"><strong>Larghezza</strong> {{ $fmtCm($r->larghezza_corriere) }} cm</div>
                        <div class="riga-dato"><strong>Spessore</strong> {{ $fmtCm($r->spessore_corriere) }} cm</div>
                        <div class="riga-dato"><strong>Peso</strong> {{ $fmtKg($r->peso_corriere) }} kg</div>
                    </td>
                </tr>
            </table>
            <table class="importi">
                <thead>
                    <tr>
                        <th>Valore pagato dal cliente (€)</th>
                        <th>Valore spedizione con misure reali (€)</th>
                        <th>Differenza dovuta (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $fmtEur((float) $r->prezzo_pagato) }}</td>
                        <td>{{ $fmtEur((float) $r->importo_dovuto) }}</td>
                        <td>{{ $fmtEur((float) $r->delta) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="corriere-pie">
                Corriere (servizio): {{ $r->corriere_nome_visualizzato ?? '—' }}
                @if ($r->data_pagamento_ordine)
                    — Data pagamento ordine: {{ $r->data_pagamento_ordine->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>
    @endforeach

    <p class="muted">
        La «differenza dovuta» è l’importo aggiuntivo stimato rispetto al valore già pagato. La data nella colonna corriere è la data di registrazione delle misure in pratica (es. import da gestionale).
    </p>
</body>
</html>
