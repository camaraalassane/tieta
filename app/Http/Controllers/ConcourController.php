<?php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\User;
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
            (new Middleware('permission:create concours'))->only(['create', 'store']),
            (new Middleware('permission:read concours'))->only(['index', 'show']),
            (new Middleware('permission:update concours'))->only(['edit', 'update']),
            (new Middleware('permission:delete concours'))->only(['destroy', 'destroyBulk']),
        ];
    }

    public function index(ConcourIndexRequest $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $query = Concour::query()->with('piecesComplementaires');

        if (!$currentUser->hasRole('superadmin')) {
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

        $concours = $query->get()->map(function ($concour) {
            return [
                'id'            => $concour->id,
                'intitule'      => $concour->intitule,
                'description'   => $concour->description,
                'organisateur'  => $concour->organisateur,
                'diplome_min'   => $concour->diplome_min,
                'date_limite'   => $concour->date_limite,
                'age'           => $concour->age,
                'statut'        => $concour->statut,
                'created_at'    => $concour->created_at,
                'updated_at'    => $concour->updated_at,
                'avis'          => $concour->avis ? Storage::url($concour->avis) : null,
                'pieces'        => $concour->piecesComplementaires->map(function ($piece) {
                    return [
                        'id'           => $piece->id,
                        'nom_document' => $piece->nom_document,
                        'is_required'  => (bool) $piece->is_required,
                    ];
                }),
            ];
        });

        return Inertia::render('Concours/Index', [
            'title'    => 'Concours',
            'filters'  => $request->all(['search', 'field', 'order']),
            'concours' => $concours,
            'total'    => (int) Concour::count(),
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('superadmin')) {
            abort(403, "Action non autorisée. Seul le superadmin peut créer un concours.");
        }

        $validatedData = $request->validate([
            'intitule'      => 'required|string|max:255',
            'description'   => 'required|string',
            'organisateur'  => 'required|string',
            'avis'          => 'required',
            'diplome_min'   => 'nullable|string',
            'date_limite'   => 'required|date',
            'age'           => 'required|integer',
            'statut'        => 'required|string',
            'pieces'        => 'nullable|array',
            'pieces.*.nom_document' => 'required|string',
            'pieces.*.is_required'  => 'required|boolean',
        ]);

        if ($request->hasFile('avis')) {
            $path = $request->file('avis')->store('Uploads/Avis', 'public');
            $validatedData['avis'] = $path;
        }

        $concours = Concour::create($validatedData);

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

        // ⭐ NOTIFICATION - Rôle corrigé de 'candidat' à 'operator'
        try {
            // Récupérer tous les utilisateurs avec le rôle 'operator' (candidats)
            $candidats = User::whereHas('roles', function ($q) {
                $q->where('name', 'operator'); // Changé de 'candidat' à 'operator'
            })->get();

            \Illuminate\Support\Facades\Log::info("Nombre de candidats trouvés: " . $candidats->count());

            if ($candidats->count() > 0) {
                \Illuminate\Support\Facades\Notification::send($candidats, new \App\Notifications\NewConcoursNotification($concours));
                \Illuminate\Support\Facades\Log::info("Notification envoyée à {$candidats->count()} candidats pour le concours: {$concours->intitule}");
            } else {
                \Illuminate\Support\Facades\Log::warning("Aucun candidat avec le rôle 'operator' trouvé");
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur lors de l'envoi des notifications: " . $e->getMessage());
        }

        return redirect()->route('concours.index')->with('success', 'Concours créé avec succès');
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $concours = Concour::findOrFail($id);

            $validatedData = $request->validate([
                'intitule'              => 'required|string|max:255',
                'description'           => 'nullable|string',
                'organisateur'          => 'required|string',
                'avis'                  => 'nullable',
                'diplome_min'           => 'nullable|string',
                'date_limite'           => 'required|date',
                'age'                   => 'nullable|integer',
                'statut'                => 'required|string',
                'removed_pieces_ids'    => 'nullable|array',
                'pieces'                => 'nullable|array',
                'pieces.*.id'           => 'nullable',
                'pieces.*.nom_document' => 'required|string|max:255',
                'pieces.*.is_required'  => 'required|boolean',
            ]);

            if (!empty($request->removed_pieces_ids)) {
                $concours->piecesComplementaires()->whereIn('id', $request->removed_pieces_ids)->delete();
            }

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
            return back()->with('success', 'Le concours "' . $concours->intitule . '" a été mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur Update Concours ID {$id} : " . $e->getMessage());
            return back()->withErrors(['error' => 'Échec de la mise à jour : ' . $e->getMessage()]);
        }
    }

    public function destroy(Concour $concour)
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->hasRole('superadmin')) {
            abort(403, "Action réservée au superadmin.");
        }

        $concour->delete();
        return back()->with('success', 'Concours supprimé avec succès.');
    }

    public function updateExpiredStatus()
    {
        $expiredCount = Concour::where('statut', 'Actif')
            ->where('date_limite', '<', Carbon::today())
            ->update(['statut' => 'Inactif']);

        return back()->with('success', "$expiredCount concours ont été mis à jour en Inactif.");
    }
}
