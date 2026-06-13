<?php

namespace App\Support\Cliente;

final class ClienteNotificazioneItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $titolo,
        public readonly string $descrizione,
        public readonly string $url,
        public readonly int $contagem = 0,
        public readonly bool $grave = false,
        public readonly bool $informativo = false,
    ) {}

    public function contaPerBadge(): bool
    {
        return ! $this->informativo && $this->contagem > 0;
    }
}
