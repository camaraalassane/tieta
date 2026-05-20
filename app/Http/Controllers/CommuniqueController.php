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
use App\Helpers\TracabiliteHelper;

class CommuniqueController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $concours = collect();

        if ($user->hasRole('superadmin')) {
            $concours = Concour::orderBy('created_at', 'desc')->get();
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $concours = Concour::where('service_id', $service->id)->orderBy('created_at', 'desc')->get();
            }
        } elseif ($user->hasRole('admin')) {
            $concours = $user->concoursGeres()->orderBy('created_at', 'desc')->get();
        }

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

        $communiques = $communiquesQuery->orderBy('created_at', 'desc')->get()->map(function ($item) {
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

    public function store(Request $request)
    {
        return $this->save($request, false);
    }
    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->save($request, true);
    }

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
        if ($isUpdate) $rules['id'] = 'required|exists:traitements,id';

        $request->validate($rules);

        $concour = Concour::with('service')->findOrFail($request->concour_id);

        if (!$this->canAccessConcour($user, $concour)) {
            abort(403, 'Vous n\'avez pas accès à ce concours.');
        }

        if ($request->hasFile('fichier')) {
            $fichierPath = $request->file('fichier')->store('communiques/files', 'public');
            if ($isUpdate && $request->id) {
                $oldCommunique = Traitement::find($request->id);
                if ($oldCommunique?->fichier) Storage::disk('public')->delete($oldCommunique->fichier);
            }
        }

        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);
        $dateLimite = null;
        if ($request->filled('date_limite')) {
            try {
                $dateLimite = Carbon::parse($request->date_limite)->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        $data = [
            'id_concours' => $request->concour_id,
            'communique_titre' => $request->titre,
            'communique' => $request->contenu,
            'communique_is_active' => $isActive,
            'date_limite' => $dateLimite
        ];
        if (isset($fichierPath)) $data['fichier'] = $fichierPath;

        if ($isUpdate && $request->id) {
            $communique = Traitement::findOrFail($request->id);
            $donneesAvant = $communique->toArray();
            $communique->update($data);

            TracabiliteHelper::log(
                'Modification',
                "Modification du communiqué « {$request->titre} » pour le concours « {$concour->intitule} »",
                'communique',
                $communique->id,
                $donneesAvant,
                $communique->fresh()->toArray(),
                $concour->service_id,
                $concour->service?->nom
            );
        } else {
            $communique = Traitement::create($data);

            TracabiliteHelper::log(
                'Création',
                "Création du communiqué « {$request->titre} » pour le concours « {$concour->intitule} »",
                'communique',
                $communique->id,
                null,
                $communique->toArray(),
                $concour->service_id,
                $concour->service?->nom
            );
        }

        return redirect()->back()->with('success', 'Communiqué ' . ($isUpdate ? 'mis à jour' : 'créé') . ' avec succès');
    }

    public function publish($id)
    {
        $user = Auth::user();
        $communique = Traitement::findOrFail($id);
        $concour = Concour::with('service')->findOrFail($communique->id_concours);

        if (!$this->canAccessConcour($user, $concour)) abort(403);

        $communique->update(['communique_is_active' => true]);

        TracabiliteHelper::log(
            'Publication',
            "Publication du communiqué « {$communique->communique_titre} »",
            'communique',
            $communique->id,
            null,
            null,
            $concour->service_id,
            $concour->service?->nom
        );

        return redirect()->back()->with('success', 'Communiqué publié');
    }

    public function unpublish($id)
    {
        $user = Auth::user();
        $communique = Traitement::findOrFail($id);
        $concour = Concour::with('service')->findOrFail($communique->id_concours);

        if (!$this->canAccessConcour($user, $concour)) abort(403);

        $communique->update(['communique_is_active' => false]);

        TracabiliteHelper::log(
            'Dépublication',
            "Dépublication du communiqué « {$communique->communique_titre} »",
            'communique',
            $communique->id,
            null,
            null,
            $concour->service_id,
            $concour->service?->nom
        );

        return redirect()->back()->with('success', 'Communiqué dépublié');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $communique = Traitement::findOrFail($id);
        $concour = Concour::with('service')->findOrFail($communique->id_concours);

        if (!$this->canAccessConcour($user, $concour)) abort(403);

        $communiqueData = $communique->toArray();
        if ($communique->fichier) Storage::disk('public')->delete($communique->fichier);
        $communique->delete();

        TracabiliteHelper::log(
            'Suppression',
            "Suppression du communiqué « {$communiqueData['communique_titre']} »",
            'communique',
            $communiqueData['id'],
            $communiqueData,
            null,
            $concour->service_id,
            $concour->service?->nom
        );

        return redirect()->back()->with('success', 'Communiqué supprimé');
    }

    public function getActiveCommuniques()
    {
        $communiques = Traitement::with('concour')
            ->where('communique_is_active', true)->whereNotNull('communique')->where('communique', '!=', '')
            ->orderBy('created_at', 'desc')->get()->map(function ($item) {
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

    private function canAccessConcour($user, $concour): bool
    {
        if ($user->hasRole('superadmin')) return true;
        if ($user->hasRole('gerant')) {
            $s = $user->getService();
            return $s && $concour->service_id == $s->id;
        }
        if ($user->hasRole('admin')) return $user->concoursGeres()->where('concour_id', $concour->id)->exists();
        return false;
    }
}
