<?php

namespace App\Support;

use App\Models\metodo_pagamento_ordine;
use App\Models\metodo_pagamento_rimborso;
use App\Models\metodo_pagamento_wallet_ricarica;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class BackofficeMetodiPagamentoConfig
{
    public const CONTESTO_ORDINI = 'ordini';

    public const CONTESTO_WALLET = 'wallet';

    public const CONTESTO_RIMBORSO = 'rimborso';

    /**
     * @return list<array{id: string, label: string, description: string}>
     */
    public static function contesti(): array
    {
        return [
            [
                'id' => self::CONTESTO_ORDINI,
                'label' => 'Ordini',
                'description' => 'Checkout ordini e pagamento spedizioni',
            ],
            [
                'id' => self::CONTESTO_WALLET,
                'label' => 'Wallet',
                'description' => 'Ricariche wallet del cliente',
            ],
            [
                'id' => self::CONTESTO_RIMBORSO,
                'label' => 'Rimborso',
                'description' => 'Metodi di rimborso al cliente',
            ],
        ];
    }

    public static function isContestoValido(string $contesto): bool
    {
        return in_array($contesto, [
            self::CONTESTO_ORDINI,
            self::CONTESTO_WALLET,
            self::CONTESTO_RIMBORSO,
        ], true);
    }

    /** @return class-string<Model> */
    public static function modelClass(string $contesto): string
    {
        return match ($contesto) {
            self::CONTESTO_ORDINI => metodo_pagamento_ordine::class,
            self::CONTESTO_WALLET => metodo_pagamento_wallet_ricarica::class,
            self::CONTESTO_RIMBORSO => metodo_pagamento_rimborso::class,
            default => throw new InvalidArgumentException('Contesto metodi pagamento non valido.'),
        };
    }

    public static function findMetodo(string $contesto, int $id): Model
    {
        $class = self::modelClass($contesto);

        return $class::query()->findOrFail($id);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Model>
     */
    public static function metodiPerContesto(string $contesto)
    {
        $class = self::modelClass($contesto);

        return $class::query()
            ->orderBy('metodo_pagamento')
            ->orderBy('id')
            ->get();
    }

    public static function labelContesto(string $contesto): string
    {
        foreach (self::contesti() as $row) {
            if ($row['id'] === $contesto) {
                return $row['label'];
            }
        }

        return $contesto;
    }
}
