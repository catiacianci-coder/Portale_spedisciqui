<?php

namespace App\Support\Cliente;

use App\Models\rimborso;
use App\Models\Ticket;

final class ClienteNotificazioniRiepilogo
{
    /**
     * @param  list<ClienteNotificazioneItem>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $badgeTotal,
        public readonly ?Ticket $ticketInEvidenza,
        public readonly ?rimborso $rimborsoInEvidenza,
        public readonly int $ncPraticheAperte,
    ) {}

    public function haPendenti(): bool
    {
        return $this->badgeTotal > 0 || $this->items !== [];
    }
}
