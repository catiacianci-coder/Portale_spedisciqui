<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class log_errore_applicativo extends Model
{
    public $timestamps = false;

    protected $table = 'log_errori_applicativi';

    protected $fillable = [
        'user_id',
        'http_status',
        'exception_class',
        'messaggio',
        'url',
        'metodo',
        'ip',
        'trace',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
