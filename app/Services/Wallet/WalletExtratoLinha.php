<?php

namespace App\Services\Wallet;

use App\Models\User;
use Carbon\Carbon;

final class WalletExtratoLinha
{
    public function __construct(
        public readonly Carbon $sortAt,
        public readonly string $tipo,
        public readonly string $descricao,
        public readonly float $valor,
        public readonly bool $isCredito,
        public readonly ?User $usuario = null,
    ) {}
}
