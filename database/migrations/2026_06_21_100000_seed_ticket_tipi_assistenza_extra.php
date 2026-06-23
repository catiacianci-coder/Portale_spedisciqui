<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            ['codigo' => 'fattura_mancante', 'nome' => 'Non ho ricevuto la fattura', 'sort_order' => 30],
            ['codigo' => 'tracking', 'nome' => 'Non riesco a fare il tracking', 'sort_order' => 40],
            ['codigo' => 'riprenotazione_ritiro', 'nome' => 'Il corriere non è passato, voglio riprenotare', 'sort_order' => 50],
        ];

        foreach ($rows as $row) {
            if (DB::table('ticket_tipo_problemas')->where('codigo', $row['codigo'])->exists()) {
                continue;
            }
            DB::table('ticket_tipo_problemas')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('ticket_tipo_problemas')->whereIn('codigo', [
            'fattura_mancante',
            'tracking',
            'riprenotazione_ritiro',
        ])->delete();
    }
};
