<?php
// routes/channels.php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

// Channel privé pour chaque utilisateur
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
