<?php
// app/Models/ConcourSpecialite.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcourSpecialite extends Model
{
    use HasFactory;

    protected $table = 'concour_specialites';

    protected $fillable = [
        'concour_id',
        'nom',
        'slug',
        'description',
        'places_disponibles',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'places_disponibles' => 'integer',
    ];

    public function concour()
    {
        return $this->belongsTo(Concour::class);
    }

    public function candidatures()
    {
        return $this->hasMany(Candidature::class, 'specialite_id');
    }
}
