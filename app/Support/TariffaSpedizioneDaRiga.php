<?php



namespace App\Support;



use App\Models\corriere;

use App\Models\corrieri_servizi_aggiuntivi;

use App\Models\spedizione;

use App\Services\ServiziAggiuntiviPrezzoService;



/**

 * Calcola e persiste il breakdown economico per spedizione (tabella tariffe_spediziones).

 */

final class TariffaSpedizioneDaRiga

{

    /**

     * @param  array<string, mixed>  $it  Riga carrello normalizzata

     * @param  array<int, array<string, mixed>>  $servizi

     * @return array<string, mixed>

     */

    public static function attributiDaRigaCarrello(

        array $it,

        spedizione $spedizione,

        float $costoTrasportoListino,

        array $servizi,

        ?corriere $corriere = null,

        ?float $aliquotaIva = null,

        float $commissioniPct = 0,

    ): array {

        $costoTrasporto = round($costoTrasportoListino, 2);

        $fuelPct = $corriere ? (float) ($corriere->fuel ?? 0) : 0.0;

        $costoFuel = $fuelPct > 0

            ? round($costoTrasporto * ($fuelPct / 100), 2)

            : 0.0;



        $trasportoBase = round((float) ($it['trasporto_base_iva_esc'] ?? $it['trasporto_iva_esc'] ?? 0), 2);

        $totaleClienteTrasporto = round((float) ($it['trasporto_iva_esc'] ?? 0), 2);

        $totaleClienteTrasportoWallet = round((float) ($it['trasporto_wallet_iva_esc'] ?? 0), 2);

        $ricaricoTrasporto = round(max(0, $trasportoBase - $costoTrasporto - $costoFuel), 2);



        $costoServizi = self::costoAcquistoServizi($costoTrasporto, $servizi);

        $clienteServizi = round((float) ($it['extra_servizi_iva_esc'] ?? 0), 2);

        $totaleSpedizione = round((float) ($it['netto_iva_esc'] ?? ($totaleClienteTrasporto + $clienteServizi)), 2);

        $totaleSpedizioneWallet = round(

            (float) ($it['netto_wallet_iva_esc'] ?? ($totaleClienteTrasportoWallet + $clienteServizi)),

            2,

        );



        $costoTotaleNostro = round($costoTrasporto + $costoFuel + $costoServizi, 2);

        $margineLordo = round($totaleSpedizione - $costoTotaleNostro, 2);



        $aliquota = $aliquotaIva ?? TariffaSpedizioneClienteIvato::aliquotaIva();

        $clienteIvato = TariffaSpedizioneClienteIvato::calcolaDaNetto(

            $totaleSpedizione,

            $aliquota,

            $commissioniPct,

        );

        $clienteIvatoWallet = TariffaSpedizioneClienteIvato::calcolaDaNetto(

            $totaleSpedizioneWallet,

            $aliquota,

            0,

        );



        return [

            'spedizione_id' => $spedizione->id,

            'codice_interno' => $spedizione->codice_interno,

            'costo_trasporto' => $costoTrasporto,

            'costo_fuel' => $costoFuel,

            'ricarico_trasporto' => $ricaricoTrasporto,

            'totale_cliente' => $totaleClienteTrasporto,

            'totale_cliente_wallet' => $totaleClienteTrasportoWallet,

            'costo_servizi_aggiuntivi' => $costoServizi,

            'cliente_servizi_aggiuntivi' => $clienteServizi,

            'totale_spedizione' => $totaleSpedizione,

            'totale_spedizione_wallet' => $totaleSpedizioneWallet,

            'margine_lordo' => $margineLordo,

            'cliente_ivato' => $clienteIvato,

            'cliente_ivato_wallet' => $clienteIvatoWallet,

        ];

    }



    /**

     * @param  array<int, array<string, mixed>>  $servizi

     */

    private static function costoAcquistoServizi(float $baseListinoTrasporto, array $servizi): float

    {

        $sum = 0.0;

        foreach ($servizi as $s) {

            if (! is_array($s)) {

                continue;

            }

            $pid = isset($s['id']) ? (int) $s['id'] : 0;

            $row = $pid > 0 ? corrieri_servizi_aggiuntivi::query()->find($pid) : null;

            if (! $row) {

                continue;

            }

            if (isset($s['costo_fornitore']) && is_numeric($s['costo_fornitore'])) {

                $sum += (float) $s['costo_fornitore'];

                continue;

            }



            $merce = (float) ($s['valore_merce'] ?? 0);

            $sum += ServiziAggiuntiviPrezzoService::importoNettoListino($row, $merce, $baseListinoTrasporto);

        }



        return round($sum, 2);

    }

}

