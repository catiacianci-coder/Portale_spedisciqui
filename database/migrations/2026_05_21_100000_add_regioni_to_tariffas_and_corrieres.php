<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffas', function (Blueprint $table) {
            $table->decimal('sicilia', 10, 2)->nullable()->after('nazione_arrivo');
            $table->decimal('calabria', 10, 2)->nullable()->after('sicilia');
            $table->decimal('sardegna', 10, 2)->nullable()->after('calabria');
            $table->string('varie1')->nullable()->after('sardegna');
            $table->string('varie2')->nullable()->after('varie1');
            $table->string('varie3')->nullable()->after('varie2');
        });

        Schema::table('corrieres', function (Blueprint $table) {
            $table->boolean('sicilia')->default(false)->after('piattaforma');
            $table->boolean('calabria')->default(false)->after('sicilia');
            $table->boolean('sardegna')->default(false)->after('calabria');
        });

        if (Schema::hasTable('corrieres') && DB::table('corrieres')->where('id', 4)->exists()) {
            DB::table('corrieres')->where('id', 4)->update([
                'sicilia' => true,
                'calabria' => true,
                'sardegna' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tariffas', function (Blueprint $table) {
            $table->dropColumn(['sicilia', 'calabria', 'sardegna', 'varie1', 'varie2', 'varie3']);
        });

        Schema::table('corrieres', function (Blueprint $table) {
            $table->dropColumn(['sicilia', 'calabria', 'sardegna']);
        });
    }
};
