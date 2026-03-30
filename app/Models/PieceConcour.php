<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PieceConcour extends Model
{
    protected $table = 'pieces_concours';
    protected $fillable = ['concour_id', 'nom_document', 'slug', 'is_required', 'description'];

    public function concour()
    {
        return $this->belongsTo(Concour::class);
    }
}