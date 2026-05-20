<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Le champ :attribute doit être accepté.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'nom',
        'prenom' => 'prénom',
        'email' => 'email',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
    ],

];
