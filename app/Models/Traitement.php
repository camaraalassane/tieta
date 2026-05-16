<?php
// app/Models/Traitement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Traitement extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_concours',
        'communique',
        'fichier', // ⭐ Ajouter le champ fichier
        'communique_titre',
        'communique_published_at',
        'communique_is_active',
        'date_limite'
    ];

    protected $casts = [
        'communique_published_at' => 'datetime',
        'communique_is_active' => 'boolean',
        'date_limite' => 'date',
    ];

    // Accesseur pour l'URL du fichier
    public function getFichierUrlAttribute()
    {
        if ($this->fichier && Storage::disk('public')->exists($this->fichier)) {
            return Storage::url($this->fichier);
        }
        return null;
    }

    // Relation avec Concour
    public function concour()
    {
        return $this->belongsTo(Concour::class, 'id_concours');
    }

    // Scope pour les communiqués actifs
    public function scopeActiveCommuniques($query)
    {
        return $query->where('communique_is_active', true)
            ->whereNotNull('communique')
            ->where('communique', '!=', '');
    }

    // Scope pour les communiqués non expirés
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('date_limite')
                ->orWhere('date_limite', '>=', Carbon::today());
        });
    }

    // Vérifier si le communiqué est expiré
    public function isExpired()
    {
        if (!$this->date_limite) {
            return false;
        }
        return Carbon::parse($this->date_limite)->isPast();
    }

    // Publier un communiqué
    public function publishCommunique()
    {
        $this->update([
            'communique_published_at' => now(),
            'communique_is_active' => true
        ]);
    }

    // Dépublier un communiqué
    public function unpublishCommunique()
    {
        $this->update([
            'communique_is_active' => false
        ]);
    }
}
