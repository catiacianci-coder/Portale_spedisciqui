<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            $table->boolean('attivo')->default(false)->after('user_id');
            $table->index(['user_id', 'attivo']);
        });

        $userIds = DB::table('anagrafiche')->distinct()->pluck('user_id');
        foreach ($userIds as $userId) {
            $lastId = DB::table('anagrafiche')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->value('id');

            if ($lastId) {
                DB::table('anagrafiche')->where('user_id', $userId)->update(['attivo' => false]);
                DB::table('anagrafiche')->where('id', $lastId)->update(['attivo' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'attivo']);
            $table->dropColumn('attivo');
        });
    }
};
