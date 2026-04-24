<?php
// app/Models/Concour.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Concour extends Model
{
    use HasFactory;

    protected $fillable = [
        'intitule',
        'description',
        'organisateur',
        'avis',
        'diplome_min',
        'date_limite',
        'age',
        'statut',
        'has_specialites',
        'service_id', // ⭐ Ajouter service_id dans fillable
    ];

    protected $casts = [
        'date_limite' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'has_specialites' => 'boolean',
    ];

    protected $appends = ['date_formatee'];

    public function getDateFormateeAttribute()
    {
        return $this->created_at?->format('d M') ?? '';
    }

    public function piecesComplementaires()
    {
        return $this->hasMany(PieceConcour::class, 'concour_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'concour_admins');
    }

    public function candidatures()
    {
        return $this->hasMany(Candidature::class);
    }

    public function resultats()
    {
        return $this->hasMany(Resultat::class, 'concour_id');
    }

    // Nouvelle relation pour les spécialités
    public function specialites()
    {
        return $this->hasMany(ConcourSpecialite::class, 'concour_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope pour filtrer les concours selon l'utilisateur connecté
     * ⭐ CORRECTION : Ne pas utiliser auth() dans un scope de modèle
     */
    public function scopeForUser($query, $user)
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        // Ne voir que les concours du service auquel l'utilisateur appartient
        $serviceId = $user->getServiceId();

        if ($serviceId) {
            return $query->where('service_id', $serviceId);
        }

        // Si l'utilisateur n'a pas de service, ne retourner aucun résultat
        return $query->whereRaw('1 = 0');
    }
}
