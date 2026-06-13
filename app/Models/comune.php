<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class comune extends Model
{
    protected $table = 'comuni'; 

    // DICIAMO A LARAVEL QUALE È LA CHIAVE PRIMARIA
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'cap', 
        'comune', 
        'provincia',
        'regione', 
        'paese',
        'attivo'
    ];
}