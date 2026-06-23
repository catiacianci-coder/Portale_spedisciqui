<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class livello extends Model
{
    protected $table = 'livellis';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'denominazione',
    ];
}
