<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
            $table->boolean('autoriza_debito_wallet')->default(true)->after('tipo_utente');
            $table->boolean('is_premium')->default(false)->after('autoriza_debito_wallet');
            $table->boolean('is_account_disabled')->default(false)->after('is_premium');
            $table->boolean('postagem_bloqueado_pelo_bo')->default(false)->after('is_account_disabled');
            $table->string('mark', 255)->nullable()->after('postagem_bloqueado_pelo_bo');
            $table->timestamp('account_cancelled_at')->nullable()->after('mark');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 64)->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        $now = now();
        DB::table('roles')->insert([
            ['nome' => 'utente', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'super_user', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'assistenza', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'contabile', 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (DB::table('users')->where('id', 1)->exists()) {
            DB::table('role_user')->insert([
                [
                    'role_id' => 1,
                    'user_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'role_id' => 2,
                    'user_id' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'autoriza_debito_wallet',
                'is_premium',
                'is_account_disabled',
                'postagem_bloqueado_pelo_bo',
                'mark',
                'account_cancelled_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
