<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Concour;
use App\Models\Traitement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CommuniqueController extends Controller
{
    /**
     * Afficher la liste des communiqués
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        // Récupération des concours accessibles selon le rôle
        $concours = collect();

        if ($user->hasRole('superadmin')) {
            $concours = Concour::orderBy('created_at', 'desc')->get();
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $concours = Concour::where('service_id', $service->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } elseif ($user->hasRole('admin')) {
            $concours = $user->concoursGeres()
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Récupération des communiqués accessibles selon le rôle
        $communiquesQuery = Traitement::with('concour');

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $service = $user->getService();
                if ($service) {
                    $communiquesQuery->whereHas('concour', function ($q) use ($service) {
                        $q->where('service_id', $service->id);
                    });
                } else {
                    $communiquesQuery->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole('admin')) {
                $communiquesQuery->whereHas('concour', function ($q) use ($user) {
                    $q->whereHas('admins', function ($sub) use ($user) {
                        $sub->where('user_id', $user->id);
                    });
                });
            }
        }

        $communiques = $communiquesQuery->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'concour_id' => $item->id_concours,
                    'concour_intitule' => $item->concour?->intitule,
                    'titre' => $item->communique_titre,
                    'contenu' => $item->communique,
                    'fichier_url' => $item->fichier ? Storage::url($item->fichier) : null,
                    'fichier_nom' => $item->fichier ? basename($item->fichier) : null,
                    'is_active' => (bool) $item->communique_is_active,
                    'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
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
     * Créer un nouveau communiqué
     */
    public function store(Request $request)
    {
        return $this->save($request, false);
    }

    /**
     * Mettre à jour un communiqué existant
     */
    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->save($request, true);
    }

    /**
     * Sauvegarder un communiqué (création ou mise à jour)
     */
    private function save(Request $request, $isUpdate = false)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $rules = [
            'concour_id' => 'required|exists:concours,id',
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'fichier' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:5120',
            'is_active' => 'nullable|in:0,1,true,false',
            'date_limite' => 'nullable|date',
        ];

        if ($isUpdate) {
            $rules['id'] = 'required|exists:traitements,id';
        }

        $request->validate($rules);

        // Vérifier l'accès au concours
        $concour = Concour::findOrFail($request->concour_id);
        $hasAccess = false;

        if ($user->hasRole('superadmin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service && $concour->service_id == $service->id) {
                $hasAccess = true;
            }
        } elseif ($user->hasRole('admin')) {
            $isAssigned = $user->concoursGeres()->where('concour_id', $concour->id)->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à ce concours.');
        }

        // Gestion du fichier uploadé
        $fichierPath = null;
        if ($request->hasFile('fichier')) {
            $fichier = $request->file('fichier');
            $fichierPath = $fichier->store('communiques/files', 'public');

            if ($isUpdate && $request->id) {
                $oldCommunique = Traitement::find($request->id);
                if ($oldCommunique && $oldCommunique->fichier) {
                    Storage::disk('public')->delete($oldCommunique->fichier);
                }
            }
        }

        // Conversion de is_active en booléen
        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);

        // Conversion de date_limite
        $dateLimite = null;
        if ($request->filled('date_limite')) {
            try {
                $dateLimite = Carbon::parse($request->date_limite)->format('Y-m-d');
            } catch (\Exception $e) {
                $dateLimite = null;
            }
        }

        // Préparer les données
        $data = [
            'id_concours' => $request->concour_id,
            'communique_titre' => $request->titre,
            'communique' => $request->contenu,
            'communique_is_active' => $isActive,
            'date_limite' => $dateLimite
        ];

        if ($fichierPath) {
            $data['fichier'] = $fichierPath;
        }

        // Créer ou mettre à jour
        if ($isUpdate && $request->id) {
            $communique = Traitement::findOrFail($request->id);
            $communique->update($data);
        } else {
            $communique = Traitement::create($data);
        }

        return redirect()->back()->with('success', $isUpdate ? 'Communiqué mis à jour avec succès' : 'Communiqué créé avec succès');
    }

    /**
     * Publier un communiqué
     */
    public function publish($id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $communique = Traitement::findOrFail($id);
        $concour = Concour::findOrFail($communique->id_concours);
        $hasAccess = false;

        if ($user->hasRole('superadmin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service && $concour->service_id == $service->id) {
                $hasAccess = true;
            }
        } elseif ($user->hasRole('admin')) {
            $isAssigned = $user->concoursGeres()->where('concour_id', $concour->id)->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à ce communiqué.');
        }

        $communique->update(['communique_is_active' => true]);

        return redirect()->back()->with('success', 'Communiqué publié avec succès');
    }

    /**
     * Dépublier un communiqué
     */
    public function unpublish($id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $communique = Traitement::findOrFail($id);
        $concour = Concour::findOrFail($communique->id_concours);
        $hasAccess = false;

        if ($user->hasRole('superadmin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service && $concour->service_id == $service->id) {
                $hasAccess = true;
            }
        } elseif ($user->hasRole('admin')) {
            $isAssigned = $user->concoursGeres()->where('concour_id', $concour->id)->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à ce communiqué.');
        }

        $communique->update(['communique_is_active' => false]);

        return redirect()->back()->with('success', 'Communiqué dépublié avec succès');
    }

    /**
     * Supprimer un communiqué
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $communique = Traitement::findOrFail($id);
        $concour = Concour::findOrFail($communique->id_concours);
        $hasAccess = false;

        if ($user->hasRole('superadmin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service && $concour->service_id == $service->id) {
                $hasAccess = true;
            }
        } elseif ($user->hasRole('admin')) {
            $isAssigned = $user->concoursGeres()->where('concour_id', $concour->id)->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à ce communiqué.');
        }

        if ($communique->fichier) {
            Storage::disk('public')->delete($communique->fichier);
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
                    'fichier_url' => $item->fichier ? Storage::url($item->fichier) : null,
                    'fichier_nom' => $item->fichier ? basename($item->fichier) : null,
                    'date_limite' => $item->date_limite ? $item->date_limite->format('d/m/Y') : null,
                    'published_at' => $item->created_at ? $item->created_at->format('d/m/Y H:i') : null,
                ];
            });

        return response()->json($communiques);
    }
}
