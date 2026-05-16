<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\TracabiliteHelper;

class ServiceImageController extends Controller
{
    public function store(Request $request, Service $service)
    {
        $user = Auth::user();
        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($service->images()->count() >= 5) {
            return response()->json(['message' => 'Maximum 5 images par service.'], 422);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $file = $request->file('image');
        $path = $file->store('services/images', 'public');

        $image = $service->images()->create([
            'path' => $path,
            'nom' => $file->getClientOriginalName(),
            'ordre' => $service->images()->count(),
        ]);

        // ⭐ TRACABILITÉ : Ajout d'une image
        TracabiliteHelper::log(
            typeAction: 'Création',
            description: "Ajout de l'image « {$file->getClientOriginalName()} » au service « {$service->nom} »",
            entite: 'ServiceImage',
            entiteId: $image->id,
            donneesApres: [
                'nom' => $file->getClientOriginalName(),
                'taille' => $file->getSize(),
                'service' => $service->nom,
            ],
            serviceId: $service->id,
            serviceNom: $service->nom,
        );

        return response()->json([
            'success' => true,
            'message' => 'Image ajoutée.',
            'image' => [
                'id' => $image->id,
                'url' => asset('storage/' . $path),
                'nom' => $image->nom,
            ],
        ]);
    }

    public function destroy(Service $service, ServiceImage $image)
    {
        $user = Auth::user();
        if (!$user->hasRole('superadmin') && $service->gerant_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($image->service_id !== $service->id) {
            return response()->json(['message' => 'Image non trouvée.'], 404);
        }

        // ⭐ Sauvegarder les infos avant suppression pour la traçabilité
        $imageNom = $image->nom;
        $imagePath = $image->path;

        Storage::disk('public')->delete($image->path);
        $image->delete();

        // ⭐ TRACABILITÉ : Suppression d'une image
        TracabiliteHelper::log(
            typeAction: 'Suppression',
            description: "Suppression de l'image « {$imageNom} » du service « {$service->nom} »",
            entite: 'ServiceImage',
            entiteId: $image->id,
            donneesAvant: [
                'nom' => $imageNom,
                'path' => $imagePath,
                'service' => $service->nom,
            ],
            serviceId: $service->id,
            serviceNom: $service->nom,
        );

        return response()->json([
            'success' => true,
            'message' => 'Image supprimée.',
        ]);
    }
}
