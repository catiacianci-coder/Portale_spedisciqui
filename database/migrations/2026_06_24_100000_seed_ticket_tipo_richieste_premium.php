<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('ticket_tipo_problemas')->where('codigo', 'richieste_premium')->exists()) {
            return;
        }

        $now = now();
        DB::table('ticket_tipo_problemas')->insert([
            'codigo' => 'richieste_premium',
            'nome' => 'Richiesta tariffe scontate (premium)',
            'sort_order' => 70,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('ticket_tipo_problemas')->where('codigo', 'richieste_premium')->delete();
    }
};
