<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Désactiver la traduction automatique -->
        <meta name="google" content="notranslate">
        <meta name="robots" content="noindex, nofollow">
        <title inertia>{{ config('app.name', 'Recrutement DTTIA') }}</title>

        <!-- Fonts -->
        
        <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
