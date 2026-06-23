<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parametri_globalis')) {
            return;
        }

        if (Schema::hasColumn('parametri_globalis', 'data_inizio') && ! Schema::hasColumn('parametri_globalis', 'inizio_validita')) {
            DB::statement('ALTER TABLE parametri_globalis CHANGE data_inizio inizio_validita DATE NULL');
        }

        if (Schema::hasColumn('parametri_globalis', 'data_fine') && ! Schema::hasColumn('parametri_globalis', 'fine_validita')) {
            DB::statement('ALTER TABLE parametri_globalis CHANGE data_fine fine_validita DATE NULL');
        }

        DB::table('parametri_globalis')
            ->whereNull('inizio_validita')
            ->update(['inizio_validita' => '2026-04-01']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parametri_globalis')) {
            return;
        }

        if (Schema::hasColumn('parametri_globalis', 'inizio_validita') && ! Schema::hasColumn('parametri_globalis', 'data_inizio')) {
            DB::statement('ALTER TABLE parametri_globalis CHANGE inizio_validita data_inizio DATE NULL');
        }

        if (Schema::hasColumn('parametri_globalis', 'fine_validita') && ! Schema::hasColumn('parametri_globalis', 'data_fine')) {
            DB::statement('ALTER TABLE parametri_globalis CHANGE fine_validita data_fine DATE NULL');
        }
    }
};
