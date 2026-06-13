<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStato extends Model
{
    protected $table = 'ticket_stati';

    public const CODIGO_NOVO = 'novo';

    public const CODIGO_ABERTO = 'aberto';

    public const CODIGO_EM_ESPERA = 'em_espera';

    public const CODIGO_EM_TRATAMENTO = 'em_tratamento';

    public const CODIGO_RESOLVIDO = 'resolvido';

    protected $fillable = [
        'codigo',
        'nome',
        'sort_order',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_stato_id');
    }

    public static function idForCodigo(string $codigo): ?int
    {
        return static::query()->where('codigo', $codigo)->value('id');
    }
}
