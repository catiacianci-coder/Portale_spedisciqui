<?php

namespace App\Services\Rimborso;

use App\Models\ordine;
use App\Models\spedizione;
use Carbon\Carbon;
use DomainException;

/**
 * Elegibilità rimborso senza API tracking (Spedisciqui).
 */
final class RimborsoElegibilidadeService
{
    public function limiteDataElegibilidade(): Carbon
    {
        $giorni = max(1, (int) config('rimborso.dias_elegibilidade_etiqueta', 30));

        return now()->subDays($giorni)->startOfDay();
    }

    public function isDentroPrazoEmissao(?Carbon $createdAt): bool
    {
        if ($createdAt === null) {
            return false;
        }

        return $createdAt->gte($this->limiteDataElegibilidade());
    }

    public function isElegivel(spedizione $spedizione, bool $ignoraRimborsoEsistente = false): bool
    {
        if (! $spedizione->ordine?->haStato(ordine::STATO_PAGATO)) {
            return false;
        }

        if (! $ignoraRimborsoEsistente && $spedizione->rimborso !== null) {
            return false;
        }

        return $this->isDentroPrazoEmissao($spedizione->created_at);
    }

    public function assertElegivel(spedizione $spedizione): void
    {
        if ($spedizione->rimborso !== null) {
            throw new DomainException('Esiste già una richiesta di rimborso per questa spedizione.');
        }

        if (! $spedizione->ordine?->haStato(ordine::STATO_PAGATO)) {
            throw new DomainException('L’ordine di questa spedizione non è pagato.');
        }

        if (! $this->isDentroPrazoEmissao($spedizione->created_at)) {
            $giorni = (int) config('rimborso.dias_elegibilidade_etiqueta', 30);
            throw new DomainException(
                'Questa spedizione non è più eleggibile: superati i '.$giorni.' giorni dalla creazione.'
            );
        }
    }
}
