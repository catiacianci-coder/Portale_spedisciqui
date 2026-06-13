<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('corrieres')
            ->where('id', 6)
            ->update([
                'tariffa_interna' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('corrieres')
            ->where('id', 6)
            ->update([
                'tariffa_interna' => true,
                'updated_at' => now(),
            ]);
    }
};
