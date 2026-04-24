<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resultat extends Model
{
    use HasFactory;

    protected $table = 'resultats';

    protected $fillable = [
        'concour_id',
        'intitule',
        'fichier',
        'statut',
        'nombre_candidat',
    ];

    protected $casts = [
        'nombre_candidat' => 'integer',
    ];

    protected $attributes = [
        'statut' => 'en preparation',
        'nombre_candidat' => 0,
    ];

    /**
     * Relation avec le concours
     */
    public function concour()
    {
        return $this->belongsTo(Concour::class, 'concour_id');
    }

    /**
     * Scope pour les résultats publiés
     */
    public function scopePublie($query)
    {
        return $query->where('statut', 'publié');
    }

    /**
     * Vérifie si le résultat est publié
     */
    public function isPublie(): bool
    {
        return $this->statut === 'publié';
    }
}
