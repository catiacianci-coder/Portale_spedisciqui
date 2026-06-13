<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ordine_stripe_rimborsi') && ! Schema::hasTable('rimborsi')) {
            $this->createRimborsiTable();

            return;
        }

        if (Schema::hasTable('ordine_stripe_rimborsi')) {
            Schema::rename('ordine_stripe_rimborsi', 'rimborsi');
        }

        $this->allineaColonneCsv();
    }

    public function down(): void
    {
        if (! Schema::hasTable('rimborsi')) {
            return;
        }

        Schema::table('rimborsi', function (Blueprint $table): void {
            if (Schema::hasColumn('rimborsi', 'spedizione_id')) {
                $table->dropForeign(['spedizione_id']);
            }
            if (Schema::hasColumn('rimborsi', 'id_metodo_pagamento_rimborsi')) {
                $table->dropForeign(['id_metodo_pagamento_rimborsi']);
            }
            $table->dropColumn([
                'spedizione_id',
                'codice_interno',
                'motivo',
                'token',
                'id_metodo_pagamento_rimborsi',
                'data_richiesta',
                'giorni',
                'data_prevista',
                'credito_avviso_letto_in',
            ]);
        });

        if (Schema::hasColumn('rimborsi', 'payment_id') && ! Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->string('stripe_refund_id', 255)->nullable();
            });
            DB::table('rimborsi')->update(['stripe_refund_id' => DB::raw('payment_id')]);
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->dropColumn('payment_id');
            });
        }

        if (Schema::hasColumn('rimborsi', 'valore') && ! Schema::hasColumn('rimborsi', 'stripe_refund_amount')) {
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->decimal('stripe_refund_amount', 14, 2)->nullable();
            });
            DB::table('rimborsi')->update(['stripe_refund_amount' => DB::raw('valore')]);
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->dropColumn('valore');
            });
        }

        if (Schema::hasColumn('rimborsi', 'data_reale') && ! Schema::hasColumn('rimborsi', 'refunded_at')) {
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->timestamp('refunded_at')->nullable();
            });
            DB::table('rimborsi')->update(['refunded_at' => DB::raw('data_reale')]);
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->dropColumn('data_reale');
            });
        }

        Schema::rename('rimborsi', 'ordine_stripe_rimborsi');
    }

    private function allineaColonneCsv(): void
    {
        if (Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
            if (! Schema::hasColumn('rimborsi', 'payment_id')) {
                Schema::table('rimborsi', function (Blueprint $table): void {
                    $table->string('payment_id', 255)->nullable();
                });
            }
            DB::table('rimborsi')->whereNotNull('stripe_refund_id')->update([
                'payment_id' => DB::raw('stripe_refund_id'),
            ]);
            $this->dropIndexIfExists('rimborsi', 'stripe_refund_id');
            Schema::table('rimborsi', function (Blueprint $table): void {
                if (Schema::hasColumn('rimborsi', 'stripe_refund_id')) {
                    $table->dropColumn('stripe_refund_id');
                }
            });
        }

        if (Schema::hasColumn('rimborsi', 'stripe_refund_amount')) {
            if (! Schema::hasColumn('rimborsi', 'valore')) {
                Schema::table('rimborsi', function (Blueprint $table): void {
                    $table->decimal('valore', 14, 2)->default(0);
                });
            }
            DB::table('rimborsi')->update(['valore' => DB::raw('stripe_refund_amount')]);
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->dropColumn('stripe_refund_amount');
            });
        }

        if (Schema::hasColumn('rimborsi', 'refunded_at')) {
            if (! Schema::hasColumn('rimborsi', 'data_reale')) {
                Schema::table('rimborsi', function (Blueprint $table): void {
                    $table->timestamp('data_reale')->nullable();
                });
            }
            DB::table('rimborsi')->update(['data_reale' => DB::raw('refunded_at')]);
            Schema::table('rimborsi', function (Blueprint $table): void {
                $table->dropColumn('refunded_at');
            });
        }

        Schema::table('rimborsi', function (Blueprint $table): void {
            if (! Schema::hasColumn('rimborsi', 'spedizione_id')) {
                $table->foreignId('spedizione_id')->nullable()->constrained('spedizionis')->nullOnDelete();
            }
            if (! Schema::hasColumn('rimborsi', 'codice_interno')) {
                $table->string('codice_interno', 40)->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'motivo')) {
                $table->text('motivo')->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'token')) {
                $table->string('token', 255)->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'id_metodo_pagamento_rimborsi')) {
                $table->foreignId('id_metodo_pagamento_rimborsi')->nullable()
                    ->constrained('metodo_pagamento_rimborsi')->nullOnDelete();
            }
            if (! Schema::hasColumn('rimborsi', 'data_richiesta')) {
                $table->timestamp('data_richiesta')->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'giorni')) {
                $table->unsignedSmallInteger('giorni')->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'data_prevista')) {
                $table->date('data_prevista')->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'credito_avviso_letto_in')) {
                $table->timestamp('credito_avviso_letto_in')->nullable();
            }
            if (! Schema::hasColumn('rimborsi', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id', 255)->nullable();
            }
        });

        DB::table('rimborsi')->whereNull('data_richiesta')->update([
            'data_richiesta' => DB::raw('COALESCE(data_reale, created_at)'),
        ]);
    }

    private function dropIndexIfExists(string $table, string $column): void
    {
        $rows = DB::select(
            'SHOW INDEX FROM `'.$table.'` WHERE Column_name = ? AND Non_unique = 0',
            [$column],
        );

        foreach ($rows as $row) {
            $key = $row->Key_name ?? null;
            if ($key && $key !== 'PRIMARY') {
                DB::statement('ALTER TABLE `'.$table.'` DROP INDEX `'.$key.'`');
            }
        }
    }

    private function createRimborsiTable(): void
    {
        Schema::create('rimborsi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('spedizione_id')->nullable()->constrained('spedizionis')->nullOnDelete();
            $table->string('codice_interno', 40)->nullable();
            $table->foreignId('ordine_id')->constrained('ordinis')->cascadeOnDelete();
            $table->text('motivo')->nullable();
            $table->string('payment_id', 255)->nullable();
            $table->string('stripe_refund_id', 255)->nullable();
            $table->string('token', 255)->nullable();
            $table->foreignId('id_metodo_pagamento_rimborsi')->nullable()
                ->constrained('metodo_pagamento_rimborsi')->nullOnDelete();
            $table->timestamp('data_richiesta')->nullable();
            $table->decimal('valore', 14, 2)->default(0);
            $table->unsignedSmallInteger('giorni')->nullable();
            $table->date('data_prevista')->nullable();
            $table->timestamp('data_reale')->nullable();
            $table->string('stripe_payment_intent_id', 255)->nullable();
            $table->timestamp('credito_avviso_letto_in')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('stripe_refund_id');
            $table->index('stripe_payment_intent_id');
            $table->index(['ordine_id', 'data_reale']);
            $table->index('spedizione_id');
        });
    }
};
