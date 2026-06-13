<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class msg_traccaimento extends Model
{
    protected $table = 'msg_traccaimentos';

    protected $fillable = [
        'corriere_id',
        'msg_ricevuto',
        'msg_per_cliente',
    ];

    public function corriere(): BelongsTo
    {
        return $this->belongsTo(corriere::class, 'corriere_id');
    }

    public function haMessaggioCliente(): bool
    {
        return trim((string) ($this->msg_per_cliente ?? '')) !== '';
    }
}
