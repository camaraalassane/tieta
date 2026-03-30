<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidaturePiece extends Model
{
    use HasFactory;

        protected $fillable = [
        'candidature_id',
        'piece_concour_id',
        'nom_fichier',
        'url_fichier',
    ];

        public function concour()
    {
        // Assurez-vous que la clé étrangère dans la table candidatures est 'concour_id'
        return $this->belongsTo(Concour::class, 'concour_id');
    }
        public function pieceConcour()
    {
        // On précise 'piece_concour_id' car c'est votre clé étrangère
        return $this->belongsTo(PieceConcour::class, 'piece_concour_id');
    }
 

}
