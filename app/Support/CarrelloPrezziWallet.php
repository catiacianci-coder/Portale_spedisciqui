<?php

namespace App\Support;

use App\Services\OrdineTotaleIvatoService;

/**
 * Prezzi trasporto Wallet per riga carrello (parallelo a trasporto_iva_esc / netto_iva_esc).
 */
final class CarrelloPrezziWallet
{
    public static function commissioniPct(): float
    {
        return app(OrdineTotaleIvatoService::class)->commissioniWalletOrdine();
    }

    public static function trasportoWalletDaStandard(float $trasportoIvaEsc): float
    {
        return PreventivoColonnePagamento::prezzoPerColonna(
            round(max(0, $trasportoIvaEsc), 2),
            self::commissioniPct(),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function sincronizzaDaTrasporto(array $item, float $trasportoIvaEsc): array
    {
        $extra = round((float) ($item['extra_servizi_iva_esc'] ?? 0), 2);
        $trasportoWallet = self::trasportoWalletDaStandard($trasportoIvaEsc);
        $item['trasporto_wallet_iva_esc'] = $trasportoWallet;
        $item['netto_wallet_iva_esc'] = round($trasportoWallet + $extra, 2);

        return $item;
    }
}
