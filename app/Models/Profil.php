<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
        protected $fillable = [
        'nom',
        'prenom',
        'sexe',
        'nina',
        'prenom_pere',
        'prenom_mere',
        'nom_mere',
        'telephone',
        'email',
        'date_naissance',
        'lieu_naissance',
        'region',
        'carte_identite',
        'photo_identite',
        'DEF',
        'BAC',
        'DUT',
        'BT',
        'CAP',
        'Licence',
        'Master',
        'Doctorat',
        'permis',


        
    ];

public function user()
{
    return $this->belongsTo(User::class);
}

    // ⭐ Formater automatiquement le NINA en majuscules
    public function setNinaAttribute($value)
    {
        $this->attributes['nina'] = $value ? strtoupper($value) : null;
    }

}
