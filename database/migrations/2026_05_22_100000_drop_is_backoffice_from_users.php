<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $superUserId = DB::table('roles')->where('nome', 'super_user')->value('id');

        if ($superUserId && Schema::hasColumn('users', 'is_backoffice')) {
            $userIds = DB::table('users')->where('is_backoffice', true)->pluck('id');
            $now = now();

            foreach ($userIds as $userId) {
                $exists = DB::table('role_user')
                    ->where('user_id', $userId)
                    ->where('role_id', $superUserId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_user')->insert([
                        'role_id' => $superUserId,
                        'user_id' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (Schema::hasColumn('users', 'is_backoffice')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_backoffice');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_backoffice')->default(false)->after('tipo_utente');
        });

        $superUserId = DB::table('roles')->where('nome', 'super_user')->value('id');
        if ($superUserId) {
            $userIds = DB::table('role_user')->where('role_id', $superUserId)->pluck('user_id');
            DB::table('users')->whereIn('id', $userIds)->update(['is_backoffice' => true]);
        }
    }
};
