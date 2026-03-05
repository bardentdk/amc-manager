<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'dossier_id', 
        'author_id', 
        'type', 
        'status', 
        'report_date', 
        'content'
    ];
    
    protected $casts = [
        'content' => 'array', 
        'report_date' => 'datetime',
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