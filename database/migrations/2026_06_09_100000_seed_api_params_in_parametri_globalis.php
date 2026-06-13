<?php

use App\Support\ParametriApi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (ParametriApi::definizioni() as $denominazione => $meta) {
            $fromEnv = '';
            if (isset($meta['env_legacy'])) {
                $fromEnv = trim((string) env($meta['env_legacy'], $meta['default'] ?? ''));
            } elseif (isset($meta['default'])) {
                $fromEnv = trim((string) $meta['default']);
            }

            $existing = DB::table('parametri_globalis')->where('denominazione', $denominazione)->first();

            if ($existing) {
                $current = trim((string) ($existing->valore_testo ?? ''));
                if ($current === '' && $fromEnv !== '') {
                    DB::table('parametri_globalis')->where('denominazione', $denominazione)->update([
                        'valore_testo' => $fromEnv,
                        'varie' => $meta['label'],
                        'updated_at' => $now,
                    ]);
                }

                continue;
            }

            DB::table('parametri_globalis')->insert([
                'denominazione' => $denominazione,
                'valore_assoluto' => null,
                'valore_percentuale' => null,
                'data_inizio' => null,
                'data_fine' => null,
                'id_metodo_pagamentos' => null,
                'varie' => $meta['label'],
                'valore_testo' => $fromEnv === '' ? null : $fromEnv,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parametri_globalis')->whereIn('denominazione', ParametriApi::denominazioni())->delete();
    }
};
