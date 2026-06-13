<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\comune;
class origine_destino extends Model
{
    protected $table = 'origine_destinos';

    protected $fillable = [
        'id_corriere',
        'id_comune_origine',
        'id_comune_destino',
        'varie',
    ];

    public function corriere(): BelongsTo
    {
        return $this->belongsTo(corriere::class, 'id_corriere');
    }

    public function origine(): BelongsTo
    {
        // Usiamo la c minuscola come nel file app/Models/comune.php
        return $this->belongsTo(comune::class, 'id_comune_origine', 'id');
    }

    public function destino(): BelongsTo
    {
        // Usiamo la c minuscola come nel file app/Models/comune.php
        return $this->belongsTo(comune::class, 'id_comune_destino', 'id');
    }
}