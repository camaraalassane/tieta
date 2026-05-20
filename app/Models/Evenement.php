<?php
// app/Models/Evenement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evenement extends Model
{
    use HasFactory;

    protected $table = 'evenements';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'service_id',
        'service_nom',
        'type_action',
        'description',
        'entite',
        'entite_id',
        'donnees_avant',
        'donnees_apres',
    ];

    protected $casts = [
        'donnees_avant' => 'array',
        'donnees_apres' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type_action', $type);
    }
}
