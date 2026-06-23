<?php

namespace App\Services\Rimborso;

use App\Models\rimborso;
use App\Models\spedizione;
use App\Models\User;
use App\Models\wallet_descrizione;
use App\Models\wallet_movimento;

final class RimborsoCreditoWalletService
{
    public function creditar(rimborso $rimborso, spedizione $spedizione, User $user): void
    {
        $descr = wallet_descrizione::query()
            ->where('codice', 'rimborso_spedizione')
            ->where('tipo', 'credito')
            ->first();

        if (! $descr) {
            throw new \RuntimeException('Descrizione wallet «rimborso_spedizione» non configurata.');
        }

        $importo = round((float) $rimborso->valore, 2);
        $riferimento = (string) ($spedizione->codice_interno ?? $rimborso->token ?? '');

        $giaAccreditato = wallet_movimento::query()
            ->where('user_id', $user->id)
            ->where('wallet_descrizione_id', $descr->id)
            ->where('ordine_id', $rimborso->ordine_id)
            ->where('riferimento', $riferimento)
            ->where('importo', $importo)
            ->exists();

        if ($giaAccreditato) {
            return;
        }

        wallet_movimento::create([
            'user_id' => $user->id,
            'tipo' => 'credito',
            'wallet_descrizione_id' => $descr->id,
            'importo' => $importo,
            'data_movimento' => now(),
            'riferimento' => $riferimento,
            'ordine_id' => $rimborso->ordine_id,
        ]);
    }
}
