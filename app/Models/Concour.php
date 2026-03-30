<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'date_limite' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    // Supprimez ces getters qui causent le problème
    // public function getCreatedAtAttribute() {
    //     return date('d-m-Y H:i', strtotime($this->attributes['created_at']));
    // }

    // public function getUpdatedAtAttribute() {
    //     return date('d-m-Y H:i', strtotime($this->attributes['updated_at']));
    // }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'concour_admins');
    }

    public function candidatures() {
        return $this->hasMany(Candidature::class);
    }

    public function resultats()
    {
        return $this->hasMany(Resultat::class, 'concour_id');
    }
}