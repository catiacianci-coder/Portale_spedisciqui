<?php

use App\Support\ParametriApi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_KEY = 'spedisci_online_quick_api_key';

    private const OLD_BASE = 'spedisci_online_quick_api_base';

    private const NEW_KEY = 'spedisci_online_eamulti_api_key';

    private const NEW_BASE = 'spedisci_online_eamulti_api_base';

    private const EAMULTI_BASE_URL = 'https://eamultiexpr.spedisci.online/api/v2';

    public function up(): void
    {
        $now = now();
        $defs = ParametriApi::definizioni();

        $oldKeyValue = trim((string) DB::table('parametri_globalis')
            ->where('denominazione', self::OLD_KEY)
            ->value('valore_testo'));

        $this->rimuoviParametro(self::OLD_KEY);
        $this->rimuoviParametro(self::OLD_BASE);

        $keyFromEnv = trim((string) env('SPEDISCI_ONLINE_API_KEY', ''));
        $keyValue = $oldKeyValue !== '' ? $oldKeyValue : $keyFromEnv;

        $this->inserisciOAggiorna(self::NEW_KEY, $keyValue, $defs[self::NEW_KEY]['label'] ?? '', $now);
        $this->inserisciOAggiorna(self::NEW_BASE, self::EAMULTI_BASE_URL, $defs[self::NEW_BASE]['label'] ?? '', $now);
    }

    public function down(): void
    {
        $now = now();
        $defs = ParametriApi::definizioni();

        $eamultiKey = trim((string) DB::table('parametri_globalis')
            ->where('denominazione', self::NEW_KEY)
            ->value('valore_testo'));

        $this->rimuoviParametro(self::NEW_KEY);
        $this->rimuoviParametro(self::NEW_BASE);

        $this->inserisciOAggiorna(self::OLD_KEY, $eamultiKey, 'Spedisci.online Quick — API key', $now);
        $this->inserisciOAggiorna(self::OLD_BASE, 'https://quicksrl.spedisci.online/api/v2', 'Spedisci.online Quick — URL base API', $now);
    }

    private function rimuoviParametro(string $denominazione): void
    {
        DB::table('parametri_globalis')->where('denominazione', $denominazione)->delete();
    }

    private function inserisciOAggiorna(string $denominazione, string $valore, string $label, $now): void
    {
        $existing = DB::table('parametri_globalis')->where('denominazione', $denominazione)->first();

        if ($existing) {
            DB::table('parametri_globalis')->where('denominazione', $denominazione)->update([
                'valore_testo' => $valore !== '' ? $valore : null,
                'varie' => $label,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('parametri_globalis')->insert([
            'denominazione' => $denominazione,
            'valore_assoluto' => null,
            'valore_percentuale' => null,
            'data_inizio' => null,
            'data_fine' => null,
            'id_metodo_pagamentos' => null,
            'varie' => $label,
            'valore_testo' => $valore !== '' ? $valore : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
