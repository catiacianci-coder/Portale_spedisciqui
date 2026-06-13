<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->unsignedBigInteger('numero')->nullable()->after('user_id');
        });

        DB::table('ordinis')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('ordinis')->where('id', $row->id)->update(['numero' => $row->id]);
            }
        });

        Schema::table('ordinis', function (Blueprint $table) {
            $table->unique('numero');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE ordinis MODIFY numero BIGINT UNSIGNED NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn('numero');
        });
    }
};
