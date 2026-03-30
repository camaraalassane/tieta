<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'emetteur_id', 
        'destinataire_id', 
        'concour_id', 
        'objet', 
        'texte', 
        'lu'
    ];

    /**
     * Relation avec le concours
     */
    public function concour(): BelongsTo
    {
        return $this->belongsTo(Concour::class, 'concour_id');
    }

    /**
     * Relation avec l'émetteur (celui qui envoie)
     */
    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    /**
     * Relation avec le destinataire (celui qui reçoit)
     * C'est cette fonction qui manquait dans votre code précédent
     */
    public function destinataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }
}
