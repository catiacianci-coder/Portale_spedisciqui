<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('ricarichi')->where('id', 4)->exists()) {
            DB::table('ricarichi')->insert([
                'id' => 4,
                'nome' => 'Standard 15%',
                'percentuale' => 15.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('corrieres', function (Blueprint $table) {
            $table->foreignId('id_ricarico')
                ->nullable()
                ->after('tariffa_interna')
                ->constrained('ricarichi')
                ->nullOnDelete();
        });

        DB::table('corrieres')
            ->where(function ($query) {
                $query->where('piattaforma', 'sendcloud')
                    ->orWhere('piattaforma', 'liccardi_tms')
                    ->orWhere('piattaforma', 'like', 'liccardi_%');
            })
            ->update(['id_ricarico' => 4]);
    }

    public function down(): void
    {
        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_ricarico');
        });

        DB::table('ricarichi')->where('id', 4)->delete();
    }
};
