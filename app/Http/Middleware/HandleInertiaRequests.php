<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Http\Request;
use App\Models\Message;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // ⭐ Récupérer les informations du service pour l'utilisateur
        $serviceData = null;
        $serviceId = null;

        if ($user) {
            // Vérifier si l'utilisateur a un service associé via la relation many-to-many
            $service = $user->service()->first();

            if ($service) {
                $serviceData = [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'slug' => $service->slug,
                    'description' => $service->description,
                    'logo_url' => $service->logo_url, // ⭐ AJOUT : URL du logo
                ];
                $serviceId = $service->id;
            }

            // Si l'utilisateur est gérant principal (hasOne)
            if (!$service && $user->serviceGerant) {
                $service = $user->serviceGerant;
                $serviceData = [
                    'id' => $service->id,
                    'nom' => $service->nom,
                    'slug' => $service->slug,
                    'description' => $service->description,
                    'logo_url' => $service->logo_url, // ⭐ AJOUT : URL du logo
                ];
                $serviceId = $service->id;
            }
        }

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'prenom'        => $user->prenom,
                    'email'         => $user->email,
                    'roles'         => $user->roles->pluck('name'),
                    'permissions'   => $user->getAllPermissions()->pluck('name'),
                    'is_superadmin' => $user->hasRole('superadmin'),

                    // ⭐ Ajout des informations du service
                    'service'       => $serviceData,
                    'service_id'    => $serviceId,

                    // Messages privés (Chat/Mail interne)
                    'unread_messages_count' => Message::where('destinataire_id', $user->id)
                        ->where('lu', false)
                        ->count(),

                    // Notifications système
                    'notifications' => $user->unreadNotifications,
                ] : null,
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }
}
