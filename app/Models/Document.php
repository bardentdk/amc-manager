<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity; 
use Spatie\Activitylog\LogOptions;

class Document extends Model
{
    use LogsActivity;
    protected $fillable = ['dossier_id', 'user_id', 'name', 'path', 'mime_type', 'size'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // Log tous les champs
            ->logOnlyDirty() // Seulement ce qui a changé
            ->dontSubmitEmptyLogs()
            ->useLogName('document');
    }

    // Accesseur pour une taille lisible (ex: 2.5 MB)
    public function getHumanSizeAttribute()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function dossier() { return $this->belongsTo(Dossier::class); }
    public function uploader() { return $this->belongsTo(User::class, 'user_id'); }
}