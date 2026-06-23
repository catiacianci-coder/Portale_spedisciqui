<?php

namespace App\Support;

use App\Models\corriere;

/**
 * Sconto volume Liccardi TMS: −3 € IVA esc. sul trasporto per ogni spedizione Liccardi
 * se l'ordine contiene almeno 10 spedizioni Liccardi (stesso ritiro / stesso ordine).
 */
final class LiccardiVolumeSconto
{
    public const MIN_SPEDIZIONI = 10;

    public const EURO_PER_SPEDIZIONE = 3.0;

    public static function isCorriereLiccardiTms(?corriere $corriere): bool
    {
        return $corriere !== null
            && PiattaformaCorriere::corriereUsaAcquistoLiccardiTms($corriere);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function isRigaLiccardi(array $item): bool
    {
        $cid = (int) ($item['corriere_id'] ?? 0);
        if ($cid <= 0) {
            return false;
        }

        $corriere = corriere::query()->find($cid);

        return self::isCorriereLiccardiTms($corriere);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function contaRigheLiccardi(array $items): int
    {
        $n = 0;
        foreach ($items as $item) {
            if (self::isRigaLiccardi($item)) {
                $n++;
            }
        }

        return $n;
    }

    public static function scontoApplicabile(int $righeLiccardi): bool
    {
        return $righeLiccardi >= self::MIN_SPEDIZIONI;
    }

    public static function trasportoScontato(float $trasportoIvaEsc): float
    {
        return round(max(0.0, $trasportoIvaEsc - self::EURO_PER_SPEDIZIONE), 2);
    }

    public static function importoScontoSuTrasporto(float $trasportoIvaEsc): float
    {
        return round(min(self::EURO_PER_SPEDIZIONE, max(0.0, $trasportoIvaEsc)), 2);
    }

    /**
     * Trasporto base a prezzo pieno (prima dello sconto volume), IVA esclusa.
     *
     * @param  array<string, mixed>  $item
     */
    public static function trasportoPieno(array $item): float
    {
        if (isset($item['trasporto_base_iva_esc_originale']) && $item['trasporto_base_iva_esc_originale'] !== '') {
            return round((float) $item['trasporto_base_iva_esc_originale'], 2);
        }

        if (isset($item['trasporto_iva_esc_originale']) && $item['trasporto_iva_esc_originale'] !== '') {
            return round((float) $item['trasporto_iva_esc_originale'], 2);
        }

        $base = round((float) ($item['trasporto_base_iva_esc'] ?? $item['trasporto_iva_esc'] ?? 0), 2);
        $scontoGia = (float) ($item['liccardi_volume_sconto_eur'] ?? 0);
        if ($scontoGia > 0) {
            return round($base + $scontoGia, 2);
        }

        return $base;
    }

    /**
     * Applica lo sconto sulle righe Liccardi se il carrello/ordine ne contiene ≥ MIN_SPEDIZIONI.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     applicato: bool,
     *     righe_liccardi: int,
     *     sconto_totale: float
     * }
     */
    public static function applicaAlCarrello(array $items): array
    {
        foreach ($items as $i => $item) {
            $basePieno = self::trasportoPieno($item);

            if (! self::isRigaLiccardi($item)) {
                unset(
                    $items[$i]['liccardi_volume_sconto_eur'],
                    $items[$i]['trasporto_base_iva_esc_originale'],
                    $items[$i]['trasporto_iva_esc_originale'],
                    $items[$i]['trasporto_wallet_iva_esc_originale'],
                );
                $items[$i] = PreventivoColonnePagamento::applicaPrezziTrasportoSuRiga($items[$i], $basePieno);
                continue;
            }

            unset(
                $items[$i]['liccardi_volume_sconto_eur'],
                $items[$i]['trasporto_base_iva_esc_originale'],
                $items[$i]['trasporto_iva_esc_originale'],
                $items[$i]['trasporto_wallet_iva_esc_originale'],
            );
            $items[$i] = PreventivoColonnePagamento::applicaPrezziTrasportoSuRiga($items[$i], $basePieno);
        }

        $righeLiccardi = self::contaRigheLiccardi($items);
        $applicato = self::scontoApplicabile($righeLiccardi);
        $scontoTotale = 0.0;

        if (! $applicato) {
            return [
                'items' => $items,
                'applicato' => false,
                'righe_liccardi' => $righeLiccardi,
                'sconto_totale' => 0.0,
            ];
        }

        foreach ($items as $i => $item) {
            if (! self::isRigaLiccardi($item)) {
                continue;
            }

            $basePieno = self::trasportoPieno($item);
            $sconto = self::importoScontoSuTrasporto($basePieno);
            $baseScontato = self::trasportoScontato($basePieno);

            $items[$i]['liccardi_volume_sconto_eur'] = $sconto;
            $items[$i]['trasporto_base_iva_esc_originale'] = $basePieno;
            $items[$i] = PreventivoColonnePagamento::applicaPrezziTrasportoSuRiga($items[$i], $baseScontato);

            $scontoTotale += $sconto;
        }

        return [
            'items' => $items,
            'applicato' => true,
            'righe_liccardi' => $righeLiccardi,
            'sconto_totale' => round($scontoTotale, 2),
        ];
    }

    public static function messaggioPreventivo(): string
    {
        return 'Ordinando almeno '.self::MIN_SPEDIZIONI.' spedizioni Liccardi nello stesso ordine (ritiro), '
            .'risparmi '.\App\Support\ImportoEuro::format(self::EURO_PER_SPEDIZIONE).' su ogni spedizione.';
    }
}
