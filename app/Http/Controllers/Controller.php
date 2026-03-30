<?php

namespace App\Http\Controllers;

/**
 * Dans Laravel 12, on ne descend plus de BaseController.
 * Cela évite les conflits avec l'interface HasMiddleware.
 */
abstract class Controller
{
    // Vous pouvez garder les traits si vous les utilisez dans vos contrôleurs
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests, 
        \Illuminate\Foundation\Validation\ValidatesRequests;
}