<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    // Champs autorisés à être remplis via create() ou update()
    protected $fillable = ['name', 'type', 'email', 'phone', 'address', 'notes'];

    // Relation : Un client a plusieurs dossiers
    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }
}