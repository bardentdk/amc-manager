<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id',
        'appointment_id', // Optionnel
        'author_id',
        'type', // closing, legal_meeting, phone_call, court_hearing
        'report_date',
        'content', // JSON
        'status', // draft, finalized
    ];

    protected $casts = [
        'report_date' => 'date',
        'content' => 'array', // Convertit automatiquement le JSON en tableau PHP
    ];

    // Relations
    public function dossier()
    {
        return $this->belongsTo(Dossier::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}