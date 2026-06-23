<?php

namespace App\Support;

use App\Models\spedizione;
use App\Models\stato_spedizione;
use App\Services\Rimborso\RimborsoElegibilidadeService;

final class RimborsoEtichettaUi
{
    public static function mostraCestino(spedizione $spedizione, RimborsoElegibilidadeService $elegibilidade): bool
    {
        return $elegibilidade->isElegivel($spedizione, false);
    }

    /**
     * Stato etichetta da spedizione_stato_id / stato_spedizionis.
     *
     * @return array{badge: string, testo: string}
     */
    public static function statoEtichettaUi(spedizione $spedizione): array
    {
        $spedizione->loadMissing(['spedizioneStato', 'rimborso']);

        $statoId = (int) ($spedizione->spedizione_stato_id ?? 0);
        $denom = trim((string) ($spedizione->spedizioneStato?->denominazione_stato ?? ''));

        $badge = match ($statoId) {
            stato_spedizione::PAGATA => 'pagato',
            stato_spedizione::GENERATA => 'pagato',
            stato_spedizione::ANNULLATA => 'in_attesa_rimborso',
            stato_spedizione::RIMBORSATA => 'rimborsata',
            stato_spedizione::NON_PAGATA => 'non_pagato',
            default => 'muted',
        };

        $testo = $denom !== '' ? ucfirst($denom) : '—';

        if ($statoId === stato_spedizione::RIMBORSATA && $spedizione->rimborso?->data_reale) {
            $testo = 'Rimborsata '.$spedizione->rimborso->data_reale->format('d/m/Y');
        }

        return [
            'badge' => $badge,
            'testo' => $testo,
        ];
    }

    public static function nomeDestinatario(spedizione $spedizione): string
    {
        $dest = SpedizioneCampiPersistenza::destinatarioArray($spedizione);
        $nome = SpedizioneClienteDati::nomeECognomeDestinatario($dest);

        return $nome !== '' ? $nome : '—';
    }

    public static function nomeServizioVisualizzato(spedizione $spedizione): string
    {
        $nome = SpedizioneServizioTabella::nomeVisualizzato($spedizione);

        return $nome !== '' ? $nome : '—';
    }

    public static function importoIvato(spedizione $spedizione): ?float
    {
        return $spedizione->prezzoClienteIvato();
    }
}
