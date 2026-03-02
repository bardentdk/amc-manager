<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id',
        'client_id', // Optionnel si lié au dossier, mais utile si RDV sans dossier (prospect)
        'title',
        'type', // legal, closing, phone, other
        'start_time',
        'end_time',
        'location',
        'notes',
        'status', // scheduled, completed, cancelled, postponed
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}