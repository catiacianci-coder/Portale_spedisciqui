<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketTipoProblema extends Model
{
    public const CODIGO_ENTREGA = 'entrega';

    public const CODIGO_ETIQUETA_NAO_GERADA = 'etiqueta_nao_gerada';

    protected $table = 'ticket_tipo_problemas';

    protected $fillable = [
        'codigo',
        'nome',
        'sort_order',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'ticket_tipo_problema_id');
    }
}
