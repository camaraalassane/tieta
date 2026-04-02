<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Concour;
use App\Models\Traitement;
use Illuminate\Support\Facades\Auth;

class CommuniqueController extends Controller
{
    /**
     * Afficher la liste des communiqués
     */
    public function index()
    {
        $user = Auth::user();

        // Récupérer les concours accessibles (admin ou superadmin)
        if ($user->hasAnyRole(['superadmin', 'admin'])) {
            $concours = Concour::orderBy('created_at', 'desc')->get();
        } else {
            $concours = $user->concoursGeres()->orderBy('created_at', 'desc')->get();
        }

        // Récupérer tous les communiqués avec les infos du concours
        $communiques = Traitement::with('concour')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'concour_id' => $item->id_concours,
                    'concour_intitule' => $item->concour?->intitule,
                    'titre' => $item->communique_titre,
                    'contenu' => $item->communique,
                    'is_active' => $item->communique_is_active,
                    'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
                    // Utiliser la date de création comme date de publication
                    'published_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i') : null,
                    'created_at' => $item->created_at->format('d/m/Y H:i'),
                    'updated_at' => $item->updated_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Communiques/index', [
            'concours' => $concours,
            'communiques' => $communiques,
        ]);
    }

    /**
     * Créer ou mettre à jour un communiqué
     */
    public function store(Request $request)
    {
        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'is_active' => 'boolean',
            'publish' => 'boolean',
            'date_limite' => 'nullable|date'
        ]);

        $user = Auth::user();

        // Vérifier l'accès au concours
        if (!$user->hasRole('superadmin') && !$user->concoursGeres()->where('concour_id', $request->concour_id)->exists()) {
            abort(403);
        }

        // Créer ou mettre à jour
        $communique = Traitement::updateOrCreate(
            ['id' => $request->id],
            [
                'id_concours' => $request->concour_id,
                'communique_titre' => $request->titre,
                'communique' => $request->contenu,
                'communique_is_active' => $request->is_active ?? false,
                // Ne plus utiliser communique_published_at, la date de création servira
                'date_limite' => $request->date_limite
            ]
        );

        return redirect()->back()->with('success', 'Communiqué enregistré avec succès');
    }

    /**
     * Publier un communiqué
     */
    public function publish($id)
    {
        $communique = Traitement::findOrFail($id);

        $user = Auth::user();
        if (!$user->hasRole('superadmin') && !$user->concoursGeres()->where('concour_id', $communique->id_concours)->exists()) {
            abort(403);
        }

        $communique->update([
            'communique_is_active' => true
            // Ne plus mettre à jour communique_published_at
        ]);

        return redirect()->back()->with('success', 'Communiqué publié avec succès');
    }

    /**
     * Dépublier un communiqué
     */
    public function unpublish($id)
    {
        $communique = Traitement::findOrFail($id);

        $user = Auth::user();
        if (!$user->hasRole('superadmin') && !$user->concoursGeres()->where('concour_id', $communique->id_concours)->exists()) {
            abort(403);
        }

        $communique->update([
            'communique_is_active' => false
        ]);

        return redirect()->back()->with('success', 'Communiqué dépublié avec succès');
    }

    /**
     * Supprimer un communiqué
     */
    public function destroy($id)
    {
        $communique = Traitement::findOrFail($id);

        $user = Auth::user();
        if (!$user->hasRole('superadmin') && !$user->concoursGeres()->where('concour_id', $communique->id_concours)->exists()) {
            abort(403);
        }

        $communique->delete();

        return redirect()->back()->with('success', 'Communiqué supprimé avec succès');
    }

    /**
     * Récupérer les communiqués actifs pour la page Welcome
     */
    public function getActiveCommuniques()
    {
        $communiques = Traitement::with('concour')
            ->where('communique_is_active', true)
            ->whereNotNull('communique')
            ->where('communique', '!=', '')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'concour_id' => $item->id_concours,
                    'concour_intitule' => $item->concour?->intitule,
                    'titre' => $item->communique_titre,
                    'contenu' => $item->communique,
                    'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
                    // Utiliser la date de création comme date de publication
                    'published_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i') : null,
                ];
            });

        return response()->json($communiques);
    }
}
