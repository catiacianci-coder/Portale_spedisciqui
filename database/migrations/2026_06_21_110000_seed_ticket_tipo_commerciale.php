<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('ticket_tipo_problemas')->where('codigo', 'commerciale')->exists()) {
            return;
        }

        $now = now();
        DB::table('ticket_tipo_problemas')->insert([
            'codigo' => 'commerciale',
            'nome' => 'Voglio parlare con un commerciale',
            'sort_order' => 60,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('ticket_tipo_problemas')->where('codigo', 'commerciale')->delete();
    }
};
