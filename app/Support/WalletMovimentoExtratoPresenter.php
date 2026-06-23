<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\wallet_movimento;
use App\Models\wallet_ricarica_richiesta;

/** Testo colonna «Descrizione» nell'estratto wallet (cliente e back office). */
final class WalletMovimentoExtratoPresenter
{
    public static function descricaoExtrato(wallet_movimento $m, bool $incluirDetalhe = true): string
    {
        $m->loadMissing(['descrizione', 'ordine', 'ricaricaRichiesta.metodoPagamentoWalletRicarica']);

        $codice = (string) ($m->descrizione?->codice ?? '');

        return match ($codice) {
            'ricarica' => self::descricaoRecarga($m, $incluirDetalhe),
            'rimborso_spedizione' => self::descricaoReembolso($m, $incluirDetalhe),
            'bonus' => '',
            'pagamento_non_conformita' => self::descricaoNonConformita($m, $incluirDetalhe),
            'pagamento_ordine' => self::descricaoPagamentoOrdine($m, $incluirDetalhe),
            default => self::descricaoGenerica($m, $incluirDetalhe),
        };
    }

    private static function descricaoRecarga(wallet_movimento $m, bool $incluirDetalhe): string
    {
        $ref = self::refRecarga($m);
        if ($ref === null) {
            return self::descricaoGenerica($m, $incluirDetalhe);
        }

        $metodo = trim((string) ($m->ricaricaRichiesta?->metodoPagamentoWalletRicarica?->metodo_pagamento ?? ''));
        $base = 'N. ordine '.$ref;

        return $metodo !== '' ? $base.' pagato con '.$metodo : $base;
    }

    private static function descricaoReembolso(wallet_movimento $m, bool $incluirDetalhe): string
    {
        $partes = [];
        if ($m->ordine_id !== null && (int) $m->ordine_id > 0) {
            $m->loadMissing('ordine');
            $codiceOrd = $m->ordine_id ? (string) (int) $m->ordine_id : '—';
            $partes[] = 'Ordine '.$codiceOrd;
        }

        $codigo = trim((string) ($m->riferimento ?? ''));
        if ($codigo !== '') {
            $partes[] = 'Codice '.$codigo;
        }

        if ($partes === []) {
            return self::descricaoGenerica($m, $incluirDetalhe);
        }

        return implode(' · ', $partes);
    }

    private static function descricaoNonConformita(wallet_movimento $m, bool $incluirDetalhe): string
    {
        $rif = trim((string) ($m->riferimento ?? ''));
        if ($rif !== '') {
            return 'Pratica NC '.$rif;
        }

        return self::descricaoGenerica($m, $incluirDetalhe);
    }

    private static function descricaoPagamentoOrdine(wallet_movimento $m, bool $incluirDetalhe): string
    {
        if ($m->ordine_id !== null && (int) $m->ordine_id > 0) {
            $m->loadMissing('ordine');
            $codice = $m->ordine_id ? (string) (int) $m->ordine_id : '—';

            return 'Ordine '.$codice;
        }

        return self::descricaoGenerica($m, $incluirDetalhe);
    }

    private static function descricaoGenerica(wallet_movimento $m, bool $incluirDetalhe): string
    {
        if ($incluirDetalhe) {
            $rif = trim((string) ($m->riferimento ?? ''));
            if ($rif !== '') {
                return $rif;
            }
        }

        $ref = self::refRecarga($m);
        if ($ref !== null) {
            return 'N. ordine '.$ref;
        }

        if ($m->ordine_id !== null && (int) $m->ordine_id > 0) {
            $m->loadMissing('ordine');
            $codice = $m->ordine_id ? (string) (int) $m->ordine_id : '—';

            return 'Ordine '.$codice;
        }

        return $incluirDetalhe ? '—' : '';
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
}
