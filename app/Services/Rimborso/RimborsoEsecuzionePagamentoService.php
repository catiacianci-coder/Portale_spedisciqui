<?php

namespace App\Services\Rimborso;

use App\Models\metodo_pagamento_rimborso;
use App\Models\ordine;
use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\User;
use App\Services\Cliente\ClienteNotificazioniRiepilogoService;
use App\Services\SpedizioneStatoService;
use DomainException;

/**
 * Accredito rimborso etichetta: sempre sul wallet del cliente.
 *
 * Il metodo di pagamento dell’ordine (carta, bonifico, wallet) non influisce sul canale di rimborso.
 * Trasferimento wallet → conto bancario: flusso separato su richiesta cliente.
 */
final class RimborsoEsecuzionePagamentoService
{
    public function __construct(
        private readonly RimborsoCreditoWalletService $wallet,
    ) {}

    public function esegui(rimborso $rimborso, spedizione $spedizione, User $user): void
    {
        $rimborso->refresh();

        if ($rimborso->isAccreditato()) {
            throw new DomainException('Rimborso già accreditato.');
        }

        $importo = round((float) $rimborso->valore, 2);
        if ($importo <= 0) {
            throw new DomainException('Importo rimborso non valido sul record.');
        }

        $ordine = $spedizione->ordine ?? ordine::query()->find($rimborso->ordine_id);
        if (! $ordine) {
            throw new DomainException('Ordine non trovato.');
        }

        $metodoWalletId = metodo_pagamento_rimborso::idMetodoWalletAttivo();
        if ($metodoWalletId === null) {
            throw new DomainException('Metodo rimborso wallet non configurato o disabilitato.');
        }

        $this->wallet->creditar($rimborso, $spedizione, $user);

        $rimborso->update([
            'id_metodo_pagamento_rimborsi' => $metodoWalletId,
            'stripe_refund_id' => null,
            'data_reale' => now(),
        ]);

        SpedizioneStatoService::segnaRimborsata($spedizione->fresh());

        ClienteNotificazioniRiepilogoService::pulisciCacheUtente((int) $user->id);
    }
}
