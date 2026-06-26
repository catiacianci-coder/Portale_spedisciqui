<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;

/** Valore colonna «Ordine/LdV» nell'estratto wallet (cliente e back office). */
final class WalletMovimentoRiferimentoPresenter
{
    /** Causali con riferimento derivato automaticamente dal sistema (non testo operatore). */
    public const CODICI_RIFERIMENTO_AUTOMATICO = [
        'ricarica',
        'pagamento_ordine',
        'rimborso_spedizione',
        'pagamento_non_conformita',
    ];

    public static function ordineLdv(wallet_movimento $m): string
    {
        $m->loadMissing(['descrizione', 'ordine', 'ricaricaRichiesta']);

        $codice = (string) ($m->descrizione?->codice ?? '');

        $auto = match ($codice) {
            'ricarica' => self::refRecarga($m),
            'pagamento_ordine' => self::refOrdine($m),
            'rimborso_spedizione', 'pagamento_non_conformita' => self::refTesto($m),
            default => null,
        };

        if ($auto !== null && $auto !== '') {
            return $auto;
        }

        $manuale = self::refTesto($m);

        return $manuale !== '' ? $manuale : '—';
    }

    public static function isRiferimentoAutomatico(string $codiceDescrizione): bool
    {
        return in_array($codiceDescrizione, self::CODICI_RIFERIMENTO_AUTOMATICO, true);
    }

    private static function refOrdine(wallet_movimento $m): ?string
    {
        if ($m->ordine_id !== null && (int) $m->ordine_id > 0) {
            return CodiceOrdine::format((int) $m->ordine_id);
        }

        $id = CodiceOrdine::idDaRiferimento((string) ($m->riferimento ?? ''));

        return $id !== null ? CodiceOrdine::format($id) : null;
    }

    private static function refRecarga(wallet_movimento $m): ?string
    {
        $richiesta = $m->ricaricaRichiesta;
        if ($richiesta === null) {
            return null;
        }

        $ref = trim((string) ($richiesta->numero_ordine_wallet ?? ''));
        if ($ref !== '') {
            return $ref;
        }

        return wallet_ricarica_richiesta::PREFIX_NUMERO_ORDINE_WALLET.$richiesta->id;
    }

    private static function refTesto(wallet_movimento $m): string
    {
        return trim((string) ($m->riferimento ?? ''));
    }
}
