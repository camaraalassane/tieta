<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidature extends Model
{
       protected $fillable = [
        'num_dossier',
        'demande_lettre',
        'profil_id',
        'concour_id',
        'nationalite',
        'resultat', 
        'motif',
        //'concour_id',
        //'casier_judiciaire',

        
    ];

        public function profil()
    {
        // On précise 'profil_id' car c'est le nom de votre colonne en base
        return $this->belongsTo(Profil::class, 'profil_id');
    }

    public function piecesChargees()
{
    return $this->hasMany(CandidaturePiece::class);
}

    public function concour()
    {
        return $this->belongsTo(Concour::class, 'concour_id');
    }

}
