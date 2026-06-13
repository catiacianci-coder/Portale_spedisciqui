<?php

namespace App\Support;

use App\Models\ordine;

final class RevolutOrdineTransazioneIds
{
    public static function propagaSuSpedizioni(ordine $ordine, ?string $revolutTransactionId): void
    {
        $id = trim((string) ($revolutTransactionId ?? ''));
        if ($id === '') {
            return;
        }

        $ordine->spedizioni()->update(['revolut_transaction_id' => $id]);
    }
}
