<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\User;
use App\Models\Service;
use App\Notifications\NewConcoursNotification;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ConcourIndexRequest;
use App\Http\Requests\ConcourStoreRequest;
use App\Http\Requests\ConcourUpdateRequest;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Notification;

class ConcourController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read concours|creer concours', only: ['index', 'show']),
            new Middleware('permission:create concours|creer concours', only: ['create', 'store']),
            new Middleware('permission:update concours|modifier concours', only: ['edit', 'update']),
            new Middleware('permission:delete concours|supprimer concours', only: ['destroy', 'destroyBulk']),
        ];
    }

    public function index(ConcourIndexRequest $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            abort(403, "Vous n'êtes pas authentifié.");
        }

        $query = Concour::query()->with(['piecesComplementaires', 'specialites', 'service']);

        if ($currentUser->hasRole('superadmin')) {
            // Superadmin voit tout
        } elseif ($currentUser->hasRole('gerant')) {
            $service = $currentUser->getService();
            if ($service) {
                $query->where('service_id', $service->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($currentUser->hasRole('admin')) {
            $query->whereHas('admins', function ($q) use ($currentUser) {
                $q->where('user_id', $currentUser->id);
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('intitule', 'LIKE', "%" . $request->search . "%")
                    ->orWhere('organisateur', 'LIKE', "%" . $request->search . "%")
                    ->orWhere('id', 'LIKE', "%" . $request->search . "%");
            });
        }

        if ($request->filled(['field', 'order'])) {
            $query->orderBy((string)$request->field, (string)$request->order);
        } else {
            $query->orderBy("created_at", "desc");
        }

        $concours = $query->paginate(10)->through(function ($concour) {
            return [
                'id'                => $concour->id,
                'intitule'          => $concour->intitule,
                'description'       => $concour->description,
                'organisateur'      => $concour->organisateur,
                'diplome_min'       => $concour->diplome_min,
                'date_limite'       => $concour->date_limite,
                'age'               => $concour->age,
                'statut'            => $concour->statut,
                'has_specialites'   => $concour->has_specialites ?? false,
                'service_id'        => $concour->service_id,
                'service_nom'       => $concour->service?->nom,
                'created_at'        => $concour->created_at,
                'updated_at'        => $concour->updated_at,
                'avis'              => $concour->avis ? Storage::url($concour->avis) : null,
                'pieces'            => $concour->piecesComplementaires->map(function ($piece) {
                    return [
                        'id'           => $piece->id,
                        'nom_document' => $piece->nom_document,
                        'is_required'  => (bool) $piece->is_required,
                    ];
                }),
                'specialites'       => $concour->specialites->map(function ($specialite) {
                    return [
                        'id'                 => $specialite->id,
                        'nom'                => $specialite->nom,
                        'description'        => $specialite->description,
                        'places_disponibles' => $specialite->places_disponibles,
                        'is_active'          => (bool) $specialite->is_active,
                    ];
                }),
            ];
        });

        $services = [];
        if ($currentUser->hasRole('superadmin')) {
            $services = Service::select('id', 'nom', 'description')->get();
        }

        return Inertia::render('Concours/Index', [
            'title'     => 'Concours',
            'filters'   => $request->all(['search', 'field', 'order']),
            'concours'  => $concours,
            'total'     => (int) Concour::count(),
            'services'  => $services,
            // ⭐ Passer les erreurs de session
            'flash'     => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }

    /**
     * ⭐ STORE - Avec conservation des données et affichage des erreurs
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser->hasRole('superadmin') && !$currentUser->hasRole('gerant')) {
            abort(403, "Action non autorisée. Vous n'avez pas les droits pour créer un concours.");
        }

        // ⭐ Validation avec messages personnalisés
        try {
            $validatedData = $request->validate([
                'intitule'      => 'required|string|max:255',
                'description'   => 'required|string',
                'organisateur'  => 'required|string',
                'avis'          => 'required|file|mimes:pdf|max:10240',
                'diplome_min'   => 'required|string',
                'date_limite'   => 'required|date|after:today',
                'age'           => 'required|integer|min:18|max:60',
                'statut'        => 'required|string',
                'service_id'    => 'nullable|exists:services,id',
                'has_specialites' => 'nullable|boolean',
                'specialites'   => 'nullable|array',
                'specialites.*.nom' => 'required_with:specialites|string|max:255',
                'specialites.*.description' => 'nullable|string',
                'specialites.*.places_disponibles' => 'nullable|integer|min:1',
                'pieces'        => 'nullable|array',
                'pieces.*.nom_document' => 'required|string|max:255',
                'pieces.*.is_required'  => 'required|boolean',
            ], [
                'intitule.required' => 'L\'intitulé du concours est obligatoire.',
                'description.required' => 'La description est obligatoire.',
                'organisateur.required' => 'L\'organisateur est obligatoire.',
                'avis.required' => 'Le fichier d\'avis (PDF) est obligatoire.',
                'avis.mimes' => 'L\'avis doit être un fichier PDF.',
                'diplome_min.required' => 'Le diplôme requis est obligatoire.',
                'date_limite.required' => 'La date limite est obligatoire.',
                'date_limite.after' => 'La date limite doit être postérieure à aujourd\'hui.',
                'age.required' => 'L\'âge limite est obligatoire.',
                'age.integer' => 'L\'âge doit être un nombre entier.',
                'age.min' => 'L\'âge minimum est de 18 ans.',
                'age.max' => 'L\'âge maximum est de 60 ans.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ⭐ En cas d'erreur de validation, retourner avec les erreurs
            // Les données sont automatiquement conservées par Inertia
            throw $e;
        }

        // ⭐ Déterminer le service_id
        try {
            if ($currentUser->hasRole('gerant')) {
                $service = $currentUser->getService();
                if (!$service) {
                    return back()->with('error', 'Vous n\'êtes pas associé à un service.');
                }
                $validatedData['service_id'] = $service->id;
            } elseif ($currentUser->hasRole('superadmin')) {
                if (empty($validatedData['service_id'])) {
                    return back()->with('error', 'Veuillez sélectionner un service pour ce concours.');
                }
            }

            // ⭐ Gestion du fichier avis
            if ($request->hasFile('avis')) {
                $path = $request->file('avis')->store('Uploads/Avis', 'public');
                $validatedData['avis'] = $path;
            }

            DB::beginTransaction();

            // ⭐ Création du concours
            $concours = Concour::create($validatedData);
            Log::info('Concours créé', ['id' => $concours->id, 'intitule' => $concours->intitule]);

            // ⭐ Création des pièces complémentaires
            if ($request->has('pieces')) {
                foreach ($request->pieces as $piece) {
                    $concours->piecesComplementaires()->create([
                        'nom_document' => $piece['nom_document'],
                        'description'  => $piece['description'] ?? null,
                        'slug'         => \Illuminate\Support\Str::slug($piece['nom_document'], '_'),
                        'is_required'  => $piece['is_required']
                    ]);
                }
            }

            // ⭐ Création des spécialités
            if ($request->has('has_specialites') && $request->has_specialites) {
                $concours->update(['has_specialites' => true]);

                if ($request->has('specialites')) {
                    foreach ($request->specialites as $specialite) {
                        if (!empty($specialite['nom'])) {
                            $concours->specialites()->create([
                                'nom' => $specialite['nom'],
                                'slug' => \Illuminate\Support\Str::slug($specialite['nom'] . '-' . $concours->id . '-' . uniqid()),
                                'description' => $specialite['description'] ?? null,
                                'places_disponibles' => $specialite['places_disponibles'] ?? null,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // ⭐ Notification aux candidats (en arrière-plan, ne bloque pas)
            try {
                $candidats = User::whereHas('roles', function ($q) {
                    $q->where('name', 'operator');
                })->get();

                if ($candidats->count() > 0) {
                    Notification::send($candidats, new NewConcoursNotification($concours));
                    Log::info("Notification envoyée à {$candidats->count()} candidats pour le concours: {$concours->intitule}");
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi des notifications: " . $e->getMessage());
                // Ne pas bloquer la création si les notifications échouent
            }

            return redirect()->route('concours.index')
                ->with('success', 'Concours "' . $concours->intitule . '" créé avec succès !');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création concours", [
                'user_id' => $currentUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Une erreur est survenue lors de la création du concours : ' . $e->getMessage())
                ->withInput(); // ⭐ Conserver les données saisies
        }
    }

    /**
     * ⭐ UPDATE - Avec conservation des données et affichage des erreurs
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        $concours = Concour::findOrFail($id);

        // ⭐ Vérifier les droits de modification
        if (!$currentUser->hasRole('superadmin')) {
            if ($currentUser->hasRole('gerant')) {
                $service = $currentUser->getService();
                if (!$service || $concours->service_id != $service->id) {
                    abort(403, "Vous n'avez pas l'autorisation de modifier ce concours.");
                }
            } elseif ($currentUser->hasRole('admin')) {
                $isAssigned = $concours->admins()->where('user_id', $currentUser->id)->exists();
                if (!$isAssigned) {
                    abort(403, "Vous n'avez pas l'autorisation de modifier ce concours.");
                }
            }
        }

        // ⭐ Validation
        try {
            $validatedData = $request->validate([
                'intitule'              => 'required|string|max:255',
                'description'           => 'required|string',
                'organisateur'          => 'required|string',
                'avis'                  => 'nullable|file|mimes:pdf|max:10240',
                'diplome_min'           => 'required|string',
                'date_limite'           => 'required|date',
                'age'                   => 'required|integer|min:18|max:60',
                'statut'                => 'required|string',
                'has_specialites'       => 'nullable|boolean',
                'removed_pieces_ids'    => 'nullable|array',
                'pieces'                => 'nullable|array',
                'pieces.*.id'           => 'nullable',
                'pieces.*.nom_document' => 'required|string|max:255',
                'pieces.*.is_required'  => 'required|boolean',
                'specialites'           => 'nullable|array',
                'specialites.*.id'      => 'nullable',
                'specialites.*.nom'     => 'required_with:specialites|string|max:255',
                'specialites.*.description' => 'nullable|string',
                'specialites.*.places_disponibles' => 'nullable|integer|min:1',
            ], [
                'intitule.required' => 'L\'intitulé du concours est obligatoire.',
                'description.required' => 'La description est obligatoire.',
                'organisateur.required' => 'L\'organisateur est obligatoire.',
                'diplome_min.required' => 'Le diplôme requis est obligatoire.',
                'date_limite.required' => 'La date limite est obligatoire.',
                'age.required' => 'L\'âge limite est obligatoire.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        try {
            DB::beginTransaction();

            // Gestion des pièces supprimées
            if (!empty($request->removed_pieces_ids)) {
                $concours->piecesComplementaires()->whereIn('id', $request->removed_pieces_ids)->delete();
            }

            // Gestion des pièces
            if ($request->has('pieces')) {
                foreach ($request->pieces as $pieceData) {
                    $concours->piecesComplementaires()->updateOrCreate(
                        ['id' => $pieceData['id'] ?? null],
                        [
                            'nom_document' => $pieceData['nom_document'],
                            'description'  => $pieceData['description'] ?? null,
                            'slug'         => \Illuminate\Support\Str::slug($pieceData['nom_document'], '_'),
                            'is_required'  => $pieceData['is_required']
                        ]
                    );
                }
            }

            // Gestion des spécialités
            $hasSpecialites = $request->has_specialites ?? false;
            $concours->update(['has_specialites' => $hasSpecialites]);

            $existingSpecialiteIds = $concours->specialites()->pluck('id')->toArray();
            $submittedSpecialiteIds = [];

            if ($hasSpecialites && $request->has('specialites')) {
                foreach ($request->specialites as $specialiteData) {
                    if (isset($specialiteData['id']) && $specialiteData['id']) {
                        $submittedSpecialiteIds[] = $specialiteData['id'];
                    }
                }
            }

            $idsToDelete = array_diff($existingSpecialiteIds, $submittedSpecialiteIds);
            if (!empty($idsToDelete)) {
                $concours->specialites()->whereIn('id', $idsToDelete)->delete();
            }

            if ($hasSpecialites && $request->has('specialites')) {
                foreach ($request->specialites as $specialiteData) {
                    if (!empty($specialiteData['nom'])) {
                        if (isset($specialiteData['id']) && $specialiteData['id']) {
                            $concours->specialites()->where('id', $specialiteData['id'])->update([
                                'nom' => $specialiteData['nom'],
                                'slug' => \Illuminate\Support\Str::slug($specialiteData['nom'] . '-' . $concours->id),
                                'description' => $specialiteData['description'] ?? null,
                                'places_disponibles' => $specialiteData['places_disponibles'] ?? null,
                            ]);
                        } else {
                            $concours->specialites()->create([
                                'nom' => $specialiteData['nom'],
                                'slug' => \Illuminate\Support\Str::slug($specialiteData['nom'] . '-' . $concours->id . '-' . uniqid()),
                                'description' => $specialiteData['description'] ?? null,
                                'places_disponibles' => $specialiteData['places_disponibles'] ?? null,
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }

            // Gestion du fichier avis
            if ($request->hasFile('avis')) {
                if ($concours->avis && Storage::disk('public')->exists($concours->avis)) {
                    Storage::disk('public')->delete($concours->avis);
                }
                $path = $request->file('avis')->store('Uploads/Avis', 'public');
                $validatedData['avis'] = $path;
            } else {
                unset($validatedData['avis']);
            }

            $concours->update($validatedData);

            DB::commit();
            Log::info('Concours mis à jour', ['id' => $concours->id, 'intitule' => $concours->intitule]);

            return back()->with('success', 'Le concours "' . $concours->intitule . '" a été mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Update Concours ID {$id}", [
                'user_id' => $currentUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'Échec de la mise à jour : ' . $e->getMessage())
                ->withInput(); // ⭐ Conserver les données saisies
        }
    }

    public function destroy(Concour $concour)
    {
        $currentUser = Auth::user();

        if ($currentUser->hasRole('superadmin')) {
            $concour->delete();
            return back()->with('success', 'Concours supprimé avec succès.');
        }

        if ($currentUser->hasRole('gerant')) {
            $service = $currentUser->getService();
            if ($service && $concour->service_id == $service->id) {
                $concour->delete();
                return back()->with('success', 'Concours supprimé avec succès.');
            }
            abort(403, "Vous n'avez pas l'autorisation de supprimer ce concours.");
        }

        abort(403, "Seul un super administrateur ou un gérant peut supprimer un concours.");
    }

    public function updateExpiredStatus()
    {
        $expiredCount = Concour::where('statut', 'Actif')
            ->where('date_limite', '<', Carbon::today())
            ->update(['statut' => 'Inactif']);

        return back()->with('success', "$expiredCount concours ont été mis à jour en Inactif.");
    }
}
