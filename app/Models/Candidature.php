<?php
// app/Models/Candidature.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidature extends Model
{
    protected $fillable = [
        'num_dossier',
        'demande_lettre',
        'profil_id',
        'concour_id',
        'specialite_id', // Nouveau champ
        'nationalite',
        'resultat',
        'motif',
    ];

    public function profil()
    {
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

    // Nouvelle relation pour la spécialité
    public function specialite()
    {
        return $this->belongsTo(ConcourSpecialite::class, 'specialite_id');
    }
}
