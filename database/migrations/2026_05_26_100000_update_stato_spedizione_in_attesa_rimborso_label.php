<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('stato_spedizionis')) {
            return;
        }

        DB::table('stato_spedizionis')
            ->where('id', 4)
            ->update(['denominazione_stato' => 'in attesa di rimborso']);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('stato_spedizionis')) {
            return;
        }

        DB::table('stato_spedizionis')
            ->where('id', 4)
            ->update(['denominazione_stato' => 'annullata']);
    }
};
