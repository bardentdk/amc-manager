<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id',
        'amount',
        'payment_date',
        'method', // Card, Transfer, Cash, Check
        'reference',
        'status', // paid, pending, partial
    ];

    // Pour que Laravel gère la date automatiquement
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }
}