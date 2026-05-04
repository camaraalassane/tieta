<?php
// app/Models/ConcourAdmin.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcourAdmin extends Model
{
    use HasFactory;

    protected $table = 'concour_admins';

    protected $fillable = [
        'concour_id',
        'user_id',
        'service_id',
    ];

    public function concour()
    {
        return $this->belongsTo(Concour::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
