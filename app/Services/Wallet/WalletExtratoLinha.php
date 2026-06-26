<?php

namespace App\Services\Wallet;

use App\Models\User;
use Carbon\Carbon;

final class WalletExtratoLinha
{
    public function __construct(
        public readonly int $movimentoId,
        public readonly Carbon $sortAt,
        public readonly string $dettaglio,
        public readonly string $ordineLdv,
        public readonly float $valor,
        public readonly bool $isCredito,
        public readonly ?User $usuario = null,
        public readonly ?string $notaInterna = null,
    ) {}
}
