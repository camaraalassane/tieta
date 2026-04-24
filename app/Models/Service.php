<?php
// app/Models/Service.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'logo',
        'gerant_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    // ⭐ CORRECTION : Accesseur pour l'URL complète du logo
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            // Le chemin est déjà stocké comme "services/logos/xxx.png"
            // On utilise Storage::url() qui gère correctement le lien symbolique
            return Storage::url($this->logo);
        }
        return null;
    }

    // Relation avec le gérant principal
    public function gerant()
    {
        return $this->belongsTo(User::class, 'gerant_id');
    }

    // Relation avec les concours du service
    public function concours()
    {
        return $this->hasMany(Concour::class);
    }

    // Relation avec tout le personnel du service
    public function personnel()
    {
        return $this->belongsToMany(User::class, 'service_users', 'service_id', 'user_id')
            ->withPivot('role_in_service', 'is_active')
            ->withTimestamps();
    }

    // Relation avec les admins du service
    public function admins()
    {
        return $this->belongsToMany(User::class, 'service_users', 'service_id', 'user_id')
            ->wherePivot('role_in_service', 'admin')
            ->wherePivot('is_active', true);
    }

    public function hasUser($userId)
    {
        return $this->personnel()->where('user_id', $userId)->exists();
    }

    public function isAdmin($userId)
    {
        return $this->personnel()
            ->where('user_id', $userId)
            ->wherePivot('role_in_service', 'admin')
            ->exists();
    }
}
