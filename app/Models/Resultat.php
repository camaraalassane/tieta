<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultat extends Model
{

protected $table = 'resultats'; 
    protected $fillable = [
        'intitule',
        'fichier',
        'statut',
        'nombre_candidat',
        'concour_id',
        
    ];

    public function concour()
    {
        // On précise 'concour_id' car votre table utilise cette orthographe
        return $this->belongsTo(Concour::class, 'concour_id');
    }
}

