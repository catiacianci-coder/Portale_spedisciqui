<?php

namespace App\Services\Tracking;

use App\Models\msg_traccaimento;

final class MsgTracciamentoService
{
    public function messaggioPerCliente(int $corriereId, string $msgRicevuto): string
    {
        $msgRicevuto = trim($msgRicevuto);
        if ($msgRicevuto === '') {
            return '';
        }

        $record = msg_traccaimento::query()->firstOrCreate(
            [
                'corriere_id' => $corriereId,
                'msg_ricevuto' => $msgRicevuto,
            ],
            [
                'msg_per_cliente' => null,
            ],
        );

        $perCliente = trim((string) ($record->msg_per_cliente ?? ''));
        if ($perCliente !== '') {
            return $perCliente;
        }

        return $msgRicevuto;
    }
}
