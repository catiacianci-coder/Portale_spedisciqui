<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodo_pagamento_ordinis', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 32);
            $table->string('metodo_pagamento', 120);
            $table->boolean('abilitato')->default(true);
            /** Percentuale sul netto IVA esclusa (es. -2 = sconto 2%). */
            $table->decimal('commissioni', 10, 4)->default(0);
            $table->string('varie')->nullable();
            $table->timestamps();
            $table->unique('codice');
        });

        Schema::create('metodo_pagamento_wallet_ricariches', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 32);
            $table->string('metodo_pagamento', 120);
            $table->boolean('abilitato')->default(true);
            $table->decimal('commissioni', 10, 4)->default(0);
            $table->string('varie')->nullable();
            $table->timestamps();
            $table->unique('codice');
        });

        Schema::create('metodo_pagamento_rimborsi', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 32);
            $table->string('metodo_pagamento', 120);
            $table->boolean('abilitato')->default(true);
            $table->decimal('commissioni', 10, 4)->default(0);
            $table->string('varie')->nullable();
            $table->timestamps();
            $table->unique('codice');
        });

        $now = now();

        DB::table('metodo_pagamento_ordinis')->insert([
            [
                'codice' => 'wallet',
                'metodo_pagamento' => 'Wallet',
                'abilitato' => true,
                'commissioni' => -2,
                'varie' => 'Sconto percentuale sul netto prima dell\'IVA.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codice' => 'carta',
                'metodo_pagamento' => 'Carta di credito/debito',
                'abilitato' => true,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codice' => 'bonifico',
                'metodo_pagamento' => 'Bonifico bancario',
                'abilitato' => true,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('metodo_pagamento_wallet_ricariches')->insert([
            [
                'codice' => 'carta',
                'metodo_pagamento' => 'Carta di credito/debito',
                'abilitato' => true,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codice' => 'bonifico',
                'metodo_pagamento' => 'Bonifico bancario',
                'abilitato' => true,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('metodo_pagamento_rimborsi')->insert([
            [
                'codice' => 'wallet',
                'metodo_pagamento' => 'Wallet',
                'abilitato' => true,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codice' => 'carta',
                'metodo_pagamento' => 'Carta di credito/debito',
                'abilitato' => false,
                'commissioni' => 0,
                'varie' => 'Disabilitato fino ad attivazione gateway.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codice' => 'bonifico',
                'metodo_pagamento' => 'Bonifico bancario',
                'abilitato' => false,
                'commissioni' => 0,
                'varie' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $mapOrdini = $this->mapLegacyMetodoIds('metodo_pagamento_ordinis');

        Schema::table('ordinis', function (Blueprint $table) {
            $table->unsignedBigInteger('id_metodo_pagamento_ordinis')->nullable()->after('stato');
        });

        foreach (DB::table('ordinis')->whereNotNull('id_metodo_pagamentos')->get() as $row) {
            $newId = $mapOrdini[(int) $row->id_metodo_pagamentos] ?? null;
            if ($newId) {
                DB::table('ordinis')->where('id', $row->id)->update([
                    'id_metodo_pagamento_ordinis' => $newId,
                ]);
            }
        }

        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropForeign(['id_metodo_pagamentos']);
            $table->dropColumn('id_metodo_pagamentos');
            $table->foreign('id_metodo_pagamento_ordinis')
                ->references('id')
                ->on('metodo_pagamento_ordinis')
                ->nullOnDelete();
        });

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->unsignedBigInteger('id_metodo_pagamento_ordinis')->nullable()->after('id_tariffas');
        });

        foreach (DB::table('spedizionis')->whereNotNull('id_metodo_pagamentos')->get() as $row) {
            $newId = $mapOrdini[(int) $row->id_metodo_pagamentos] ?? null;
            if ($newId) {
                DB::table('spedizionis')->where('id', $row->id)->update([
                    'id_metodo_pagamento_ordinis' => $newId,
                ]);
            }
        }

        Schema::table('spedizionis', function (Blueprint $table) {
            $table->dropForeign(['id_metodo_pagamentos']);
            $table->dropColumn('id_metodo_pagamentos');
            $table->foreign('id_metodo_pagamento_ordinis')
                ->references('id')
                ->on('metodo_pagamento_ordinis')
                ->nullOnDelete();
        });

        $mapRicarica = $this->mapLegacyMetodoIds('metodo_pagamento_wallet_ricariches');

        if (Schema::hasColumn('wallet_ricarica_richiestas', 'id_metodo_pagamentos')) {
            Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
                $table->unsignedBigInteger('id_metodo_pagamento_wallet_ricariches')->nullable()->after('stato');
            });

            foreach (DB::table('wallet_ricarica_richiestas')->whereNotNull('id_metodo_pagamentos')->get() as $row) {
                $newId = $mapRicarica[(int) $row->id_metodo_pagamentos] ?? null;
                if ($newId) {
                    DB::table('wallet_ricarica_richiestas')->where('id', $row->id)->update([
                        'id_metodo_pagamento_wallet_ricariches' => $newId,
                    ]);
                }
            }

            Schema::table('wallet_ricarica_richiestas', function (Blueprint $table) {
                $table->dropForeign(['id_metodo_pagamentos']);
                $table->dropColumn('id_metodo_pagamentos');
                $table->foreign('id_metodo_pagamento_wallet_ricariches', 'fk_wrr_metodo_ricarica')
                    ->references('id')
                    ->on('metodo_pagamento_wallet_ricariches')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * @return array<int, int> vecchio id metodo_pagamentos → nuovo id tabella contesto
     */
    private function mapLegacyMetodoIds(string $targetTable): array
    {
        $codiceByLegacyName = [
            'wallet' => ['wallet'],
            'carta' => ['carta di credito/debito', 'carta'],
            'bonifico' => ['bonifico bancario', 'bonifico'],
        ];

        $newByCodice = DB::table($targetTable)->pluck('id', 'codice');
        $map = [];

        foreach (DB::table('metodo_pagamentos')->get() as $legacy) {
            $nome = strtolower(trim((string) $legacy->metodo_pagamento));
            $codice = null;
            foreach ($codiceByLegacyName as $code => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($nome, $needle) || $nome === $needle) {
                        $codice = $code;
                        break 2;
                    }
                }
            }
            if ($codice === 'wallet' && $targetTable === 'metodo_pagamento_wallet_ricariches') {
                continue;
            }
            if ($codice && isset($newByCodice[$codice])) {
                $map[(int) $legacy->id] = (int) $newByCodice[$codice];
            }
        }

        return $map;
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback non supportato per split metodi pagamento.');
    }
};
