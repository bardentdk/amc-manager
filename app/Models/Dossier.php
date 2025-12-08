<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dossier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ref_number',
        'client_id',
        'lawyer_id',
        'type',
        'status',
        'subject',
        'description',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lawyer()
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }
    public function payments(){
        return $this->hasMany(Payment::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}