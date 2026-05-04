<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Concour;
use App\Models\Service;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ConcourMessagerieController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // ⭐ Inclure le rôle 'gerant'
        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $isSuperAdmin = $user->hasRole('superadmin');
        $isAdmin = $user->hasRole('admin');
        $isGerant = $user->hasRole('gerant');
        $isStaff = $isSuperAdmin || $isAdmin || $isGerant;

        // ⭐ Récupérer les IDs des concours accessibles selon le rôle
        $accessibleConcoursIds = [];

        if ($isSuperAdmin) {
            // Superadmin voit tous les concours
            $accessibleConcoursIds = Concour::pluck('id')->toArray();
        } elseif ($isGerant) {
            // Gérant voit les concours de son service
            $service = $user->getService();
            if ($service) {
                $accessibleConcoursIds = Concour::where('service_id', $service->id)->pluck('id')->toArray();
            }
        } elseif ($isAdmin) {
            // Admin voit les concours où il est assigné
            $accessibleConcoursIds = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->pluck('concour_id')
                ->toArray();
        }

        // Récupérer TOUS les messages des concours accessibles
        $messages = Message::with([
            'emetteur:id,name,email,prenom',
            'destinataire:id,name,email,prenom',
            'concour:id,intitule',
            'emetteur.roles:id,name'
        ])->whereIn('concour_id', $accessibleConcoursIds)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->reverse();

        // Grouper par concour_id et candidat_id
        $conversations = [];

        foreach ($messages as $msg) {
            if (!$msg->emetteur) continue;

            $isMsgFromStaff = $msg->emetteur->hasAnyRole(['admin', 'superadmin', 'gerant']);
            $candidatId = $isMsgFromStaff ? $msg->destinataire_id : $msg->emetteur_id;
            $concourId = $msg->concour_id;

            $candidat = User::find($candidatId);
            if (!$candidat || $candidat->hasAnyRole(['admin', 'superadmin', 'gerant'])) {
                continue;
            }

            $key = (string)$candidatId;

            if (!isset($conversations[$key])) {
                $concour = Concour::find($concourId);

                $conversations[$key] = [
                    'id' => $candidatId,
                    'concour_id' => $concourId,
                    'concour_intitule' => $concour ? $concour->intitule : 'Inconnu',
                    'candidat_id' => $candidatId,
                    'candidat_nom' => $candidat->name ?? 'Inconnu',
                    'candidat_prenom' => $candidat->prenom ?? '',
                    'messages' => [],
                    'unread_count' => 0,
                    'last_message' => null,
                ];
            }

            $conversations[$key]['messages'][] = $msg;

            if ($msg->destinataire_id == $user->id && $msg->lu == 0) {
                $conversations[$key]['unread_count']++;
            }

            $currentLast = $conversations[$key]['last_message'];
            if ($currentLast === null || $msg->created_at > $currentLast->created_at) {
                $conversations[$key]['last_message'] = $msg;
            }
        }

        // Trier les messages par date croissante
        foreach ($conversations as &$conv) {
            $conv['messages'] = collect($conv['messages'])->sortBy('created_at')->values()->toArray();
        }

        // Trier les conversations par date du dernier message
        $conversations = collect($conversations)->sortByDesc(function ($conv) {
            return $conv['last_message']?->created_at;
        })->values()->toArray();

        $conversations = array_slice($conversations, 0, 30);

        return Inertia::render('Concours/Messagerie/index', [
            'conversations' => $conversations,
            'listeConcours' => Concour::whereIn('id', $accessibleConcoursIds)
                ->orderBy('intitule')
                ->limit(100)
                ->get(['id', 'intitule']),
            'isAdmin' => $isStaff,
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $isSuperAdmin,
            'is_gerant' => $isGerant,
            'is_admin' => $isAdmin,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }

            // Token anti-doublon
            $submissionToken = md5($user->id . $request->texte . $request->concour_id . $request->destinataire_id);
            $cacheKey = 'message_submission_' . $submissionToken;

            if (Cache::has($cacheKey)) {
                return response()->json(['success' => true, 'message' => 'Message déjà envoyé'], 200);
            }

            Cache::put($cacheKey, true, 5);

            $request->validate([
                'concour_id' => 'required|exists:concours,id',
                'texte' => 'required|string|max:5000',
                'destinataire_id' => 'required|exists:users,id',
            ]);

            // ⭐ Vérification des droits selon le rôle
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
                $hasAccess = DB::table('concour_admins')
                    ->where('user_id', $user->id)
                    ->where('concour_id', $request->concour_id)
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Vous n\'êtes pas autorisé à envoyer des messages pour ce concours.'], 403);
            }

            $destinataire = User::find($request->destinataire_id);

            if (!$destinataire) {
                return response()->json(['error' => 'Destinataire introuvable.'], 404);
            }

            $message = Message::create([
                'emetteur_id' => $user->id,
                'destinataire_id' => $request->destinataire_id,
                'concour_id' => $request->concour_id,
                'texte' => $request->texte,
                'objet' => 'Discussion Concours - ' . ($concour ? $concour->intitule : ''),
                'lu' => 0,
            ]);

            try {
                $destinataire->notify(new NewMessageNotification($message, $user));
            } catch (\Exception $e) {
                Log::error("Erreur notification: " . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Message envoyé avec succès']);
        } catch (\Exception $e) {
            Log::error('ERREUR ENVOI MESSAGE: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function markAsRead($interlocuteurId)
    {
        Message::where('destinataire_id', Auth::id())
            ->where('emetteur_id', $interlocuteurId)
            ->where('lu', 0)
            ->update(['lu' => 1]);

        return back();
    }

    public function markMessageAsRead($messageId)
    {
        $message = Message::findOrFail($messageId);

        if ($message->destinataire_id == Auth::id() && $message->lu == 0) {
            $message->update(['lu' => 1]);
        }

        return response()->json(['success' => true]);
    }

    public function loadMoreMessages(Request $request)
    {
        $request->validate([
            'interlocutor_id' => 'required|exists:users,id',
            'concour_id' => 'required|exists:concours,id',
            'offset' => 'required|integer|min:0'
        ]);

        $user = Auth::user();

        $messages = Message::where('concour_id', $request->concour_id)
            ->where(function ($q) use ($request, $user) {
                $q->where(function ($sq) use ($request, $user) {
                    $sq->where('emetteur_id', $user->id)
                        ->where('destinataire_id', $request->interlocutor_id);
                })->orWhere(function ($sq) use ($request, $user) {
                    $sq->where('emetteur_id', $request->interlocutor_id)
                        ->where('destinataire_id', $user->id);
                });
            })
            ->with(['emetteur:id,name,email,prenom,roles', 'destinataire:id,name,email,prenom'])
            ->orderBy('created_at', 'asc')
            ->skip($request->offset)
            ->take(30)
            ->get();

        $total = Message::where('concour_id', $request->concour_id)
            ->where(function ($q) use ($request, $user) {
                $q->where('emetteur_id', $request->interlocutor_id)
                    ->orWhere('destinataire_id', $request->interlocutor_id);
            })->count();

        return response()->json([
            'messages' => $messages,
            'has_more' => ($request->offset + 30) < $total
        ]);
    }

    public function refresh()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $isSuperAdmin = $user->hasRole('superadmin');
        $isAdmin = $user->hasRole('admin');
        $isGerant = $user->hasRole('gerant');

        $accessibleConcoursIds = [];

        if ($isSuperAdmin) {
            $accessibleConcoursIds = Concour::pluck('id')->toArray();
        } elseif ($isGerant) {
            $service = $user->getService();
            if ($service) {
                $accessibleConcoursIds = Concour::where('service_id', $service->id)->pluck('id')->toArray();
            }
        } elseif ($isAdmin) {
            $accessibleConcoursIds = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->pluck('concour_id')
                ->toArray();
        }

        $messages = Message::with([
            'emetteur:id,name,email,prenom',
            'destinataire:id,name,email,prenom',
            'concour:id,intitule',
            'emetteur.roles:id,name'
        ])->whereIn('concour_id', $accessibleConcoursIds)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->reverse();

        $conversations = [];

        foreach ($messages as $msg) {
            if (!$msg->emetteur) continue;

            $isMsgFromStaff = $msg->emetteur->hasAnyRole(['admin', 'superadmin', 'gerant']);
            $candidatId = $isMsgFromStaff ? $msg->destinataire_id : $msg->emetteur_id;
            $concourId = $msg->concour_id;

            $candidat = User::find($candidatId);
            if (!$candidat || $candidat->hasAnyRole(['admin', 'superadmin', 'gerant'])) {
                continue;
            }

            $key = (string)$candidatId;

            if (!isset($conversations[$key])) {
                $concour = Concour::find($concourId);

                $conversations[$key] = [
                    'id' => $candidatId,
                    'concour_id' => $concourId,
                    'concour_intitule' => $concour ? $concour->intitule : 'Inconnu',
                    'candidat_id' => $candidatId,
                    'candidat_nom' => $candidat->name ?? 'Inconnu',
                    'candidat_prenom' => $candidat->prenom ?? '',
                    'messages' => [],
                    'unread_count' => 0,
                    'last_message' => null,
                ];
            }

            $conversations[$key]['messages'][] = $msg;

            if ($msg->destinataire_id == $user->id && $msg->lu == 0) {
                $conversations[$key]['unread_count']++;
            }

            $currentLast = $conversations[$key]['last_message'];
            if ($currentLast === null || $msg->created_at > $currentLast->created_at) {
                $conversations[$key]['last_message'] = $msg;
            }
        }

        foreach ($conversations as &$conv) {
            $conv['messages'] = collect($conv['messages'])->sortBy('created_at')->values()->toArray();
        }

        $conversations = collect($conversations)->sortByDesc(function ($conv) {
            return $conv['last_message']?->created_at;
        })->values()->toArray();

        $conversations = array_slice($conversations, 0, 30);

        return response()->json([
            'conversations' => $conversations
        ]);
    }
}
