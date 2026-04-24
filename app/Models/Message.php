<?php
// app/Models/Message.php

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
        'lu',
        'is_broadcast',
        'broadcast_subject'
    ];

    protected $casts = [
        'lu' => 'boolean',
        'is_broadcast' => 'boolean',
    ];

    public function concour(): BelongsTo
    {
        return $this->belongsTo(Concour::class, 'concour_id');
    }

    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    public function destinataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }
}
