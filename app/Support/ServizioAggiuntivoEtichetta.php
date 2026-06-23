<?php

namespace App\Support;

use App\Models\servizi_aggiuntivi;
use App\Models\spedizione_servizio_aggiuntivi;
use Illuminate\Support\Collection;

/**
 * Etichetta servizio aggiuntivo nelle tabelle spedizione (abbrev. catalogo, non checkout).
 */
final class ServizioAggiuntivoEtichetta
{
    public static function perRiga(spedizione_servizio_aggiuntivi $riga): string
    {
        $nome = self::nomeTabella($riga);
        if ($nome === '') {
            return '';
        }

        $val = isset($riga->valore_merce) && $riga->valore_merce !== null
            ? (float) $riga->valore_merce
            : null;
        if ($val !== null && $val > 0) {
            $nome .= ' ('.\App\Support\ImportoEuro::format($val).')';
        }

        return $nome;
    }

    /** Abbreviazione + importo € per tabella etichette. */
    public static function abbrevEImportoEuro(spedizione_servizio_aggiuntivi $riga): string
    {
        $abbrev = self::nomeTabella($riga);
        if ($abbrev === '') {
            return '';
        }

        $importo = self::importoEuroRiga($riga);
        if ($importo === null) {
            return $abbrev;
        }

        return $abbrev.' '.\App\Support\ImportoEuro::format($importo);
    }

    private static function importoEuroRiga(spedizione_servizio_aggiuntivi $riga): ?float
    {
        $valoreMerce = isset($riga->valore_merce) ? (float) $riga->valore_merce : 0.0;
        if ($valoreMerce > 0) {
            return $valoreMerce;
        }

        $costoCliente = isset($riga->costo_cliente) ? (float) $riga->costo_cliente : 0.0;

        return $costoCliente > 0 ? $costoCliente : null;
    }

    public static function nomeTabella(spedizione_servizio_aggiuntivi $riga): string
    {
        $testo = trim((string) (
            $riga->denominazione_servizio
            ?? $riga->corriereServizioAggiuntivo?->testo_servizio
            ?? ''
        ));

        if ($testo === '') {
            return '';
        }

        return self::abbrevPerTesto($testo) ?? $testo;
    }

    public static function abbrevPerTesto(string $testo): ?string
    {
        $norm = mb_strtolower(trim($testo));
        if ($norm === '') {
            return null;
        }

        foreach (self::catalogoConAbbrev() as $row) {
            $den = mb_strtolower(trim((string) $row->denominazione_servizio));
            $abbrev = trim((string) ($row->abbrev ?? ''));
            if ($abbrev === '' || $den === '') {
                continue;
            }
            if ($norm === $den || str_starts_with($norm, $den.' ') || str_starts_with($norm, $den)) {
                return $abbrev;
            }
        }

        return null;
    }

    /** @return Collection<int, servizi_aggiuntivi> */
    private static function catalogoConAbbrev(): Collection
    {
        static $cache = null;

        if ($cache === null) {
            $cache = servizi_aggiuntivi::query()
                ->whereNotNull('abbrev')
                ->where('abbrev', '!=', '')
                ->orderByRaw('CHAR_LENGTH(denominazione_servizio) DESC')
                ->get();
        }

        return $cache;
    }
}
