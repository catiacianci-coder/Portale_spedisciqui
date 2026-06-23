<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketTipoProblema extends Model
{
    public const CODIGO_ENTREGA = 'entrega';

    public const CODIGO_ETIQUETA_NAO_GERADA = 'etiqueta_nao_gerada';

    public const CODIGO_FATTURA_MANCANTE = 'fattura_mancante';

    public const CODIGO_TRACKING = 'tracking';

    public const CODIGO_RIPRENOTAZIONE_RITIRO = 'riprenotazione_ritiro';

    public const CODIGO_COMMERCIALE = 'commerciale';

    public const CODIGO_RICHIESTE_PREMIUM = 'richieste_premium';

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
