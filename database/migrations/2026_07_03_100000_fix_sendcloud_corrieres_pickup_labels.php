<?php

use App\Support\SendcloudCorrierePickupLabels;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Allinea pickup/consegna dei corrieri Sendcloud al first/last mile reale (listino Sendcloud).
 * Corregge InPost Address to Locker: pickup "Indirizzo" (non "Domicilio", niente calendario ritiro).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $corrieres = DB::table('corrieres')
            ->where('piattaforma', 'sendcloud')
            ->get(['id', 'codice_servizio', 'carrier_code']);

        foreach ($corrieres as $row) {
            $labels = SendcloudCorrierePickupLabels::fromCorriere(
                (string) ($row->codice_servizio ?? ''),
                (string) ($row->carrier_code ?? ''),
            );

            DB::table('corrieres')->where('id', $row->id)->update([
                'pickup' => $labels['pickup'],
                'consegna' => $labels['consegna'],
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $now = now();

        $rollback = [
            7 => ['pickup' => 'Domicilio', 'consegna' => 'Ufficio Postale'],
            8 => ['pickup' => 'Domicilio', 'consegna' => 'Punto Poste'],
            9 => ['pickup' => 'Domicilio', 'consegna' => 'Domicilio'],
            10 => ['pickup' => 'Punto Poste', 'consegna' => 'Punto Poste'],
            11 => ['pickup' => 'Domicilio', 'consegna' => 'Locker InPost'],
            12 => ['pickup' => 'Domicilio', 'consegna' => 'Locker InPost'],
        ];

        foreach ($rollback as $id => $labels) {
            DB::table('corrieres')->where('id', $id)->update([
                'pickup' => $labels['pickup'],
                'consegna' => $labels['consegna'],
                'updated_at' => $now,
            ]);
        }
    }
};
