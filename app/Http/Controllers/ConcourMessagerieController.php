<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Concour;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConcourMessagerieController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $isSuperAdmin = $user->hasRole('superadmin');
        $isAdmin = $user->hasRole('admin');
        $isStaff = $isSuperAdmin || $isAdmin;

        // ⭐ Récupérer toutes les conversations formatées pour la vue
        $query = Message::with([
            'emetteur:id,name,email',
            'destinataire:id,name,email',
            'concour:id,intitule',
            'emetteur.roles:id,name'
        ]);

        if (!$isSuperAdmin && $isAdmin) {
            $assignedConcoursIds = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->pluck('concour_id');
            $query->whereIn('concour_id', $assignedConcoursIds);
        }

        // Récupérer les 200 derniers messages
        $messages = $query->where(function ($q) use ($user) {
            $q->where('emetteur_id', $user->id)
                ->orWhere('destinataire_id', $user->id);
        })
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->reverse();

        // ⭐ Grouper par interlocuteur
        $conversations = $messages->groupBy(function ($msg) use ($user, $isStaff) {
            if ($isStaff) {
                $emetteurIsStaff = $msg->emetteur->hasAnyRole(['admin', 'superadmin']);
                return $emetteurIsStaff ? $msg->destinataire_id : $msg->emetteur_id;
            }
            return $user->id;
        });

        // ⭐ Formater exactement comme attendu par la vue
        $formattedConversations = [];
        $conversationsCount = 0;

        foreach ($conversations as $interlocutorId => $msgs) {
            if ($conversationsCount >= 30) break;

            $limitedMessages = $msgs->take(50);

            $firstMsg = $limitedMessages->first();

            $interlocutorName = $isStaff
                ? ($firstMsg && $firstMsg->emetteur_id == $interlocutorId
                    ? ($firstMsg->emetteur->name ?? 'Inconnu')
                    : ($firstMsg->destinataire->name ?? 'Inconnu'))
                : "Support Administration";

            $formattedConversations[$interlocutorId] = [
                'messages' => $limitedMessages->values(),
                'unread_count' => $msgs->where('destinataire_id', $user->id)->where('lu', 0)->count(),
                'last_message' => $msgs->last(),
                'interlocutor_name' => $interlocutorName
            ];

            $conversationsCount++;
        }

        return Inertia::render('Concours/Messagerie/index', [
            'conversations' => $formattedConversations,
            'listeConcours' => Concour::orderBy('intitule')->limit(100)->get(['id', 'intitule']),
            'isAdmin' => $isStaff
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'texte' => 'required|string|max:5000',
            'destinataire_id' => 'required|exists:users,id',
        ]);

        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $hasAccess = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->where('concour_id', $request->concour_id)
                ->exists();

            if (!$hasAccess) {
                abort(403, "Vous n'êtes pas autorisé à envoyer des messages pour ce concours.");
            }
        }

        $message = Message::create([
            'emetteur_id' => $user->id,
            'destinataire_id' => $request->destinataire_id,
            'concour_id' => $request->concour_id,
            'texte' => $request->texte,
            'objet' => 'Discussion Concours',
            'lu' => 0,
        ]);

        try {
            $destinataire = User::find($request->destinataire_id);
            if ($destinataire) {
                $destinataire->notify(new NewMessageNotification($message, $user));
                Log::info("✅ Notification envoyée à {$destinataire->name}");
            }
        } catch (\Exception $e) {
            Log::error("❌ Erreur notification: " . $e->getMessage());
        }

        return back()->with('success', 'Message envoyé avec succès');
    }

    public function markAsRead($interlocuteurId)
    {
        $updated = Message::where('destinataire_id', Auth::id())
            ->where('emetteur_id', $interlocuteurId)
            ->where('lu', 0)
            ->update(['lu' => 1]);

        return back();
    }

    // ⭐ Charger plus de messages pour une conversation (scroll infini)
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
            ->with(['emetteur:id,name,email,roles', 'destinataire:id,name,email'])
            ->orderBy('created_at', 'asc')
            ->skip($request->offset)
            ->take(30)
            ->get();

        $total = Message::where('concour_id', $request->concour_id)
            ->where(function ($q) use ($request, $user) {
                $q->where(function ($sq) use ($request, $user) {
                    $sq->where('emetteur_id', $user->id)
                        ->where('destinataire_id', $request->interlocutor_id);
                })->orWhere(function ($sq) use ($request, $user) {
                    $sq->where('emetteur_id', $request->interlocutor_id)
                        ->where('destinataire_id', $user->id);
                });
            })->count();

        return response()->json([
            'messages' => $messages,
            'has_more' => ($request->offset + 30) < $total
        ]);
    }

    // ⭐ NOUVEAU: Rafraîchir les conversations (pour l'API)
    public function refresh()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $isSuperAdmin = $user->hasRole('superadmin');
        $isAdmin = $user->hasRole('admin');
        $isStaff = $isSuperAdmin || $isAdmin;

        $query = Message::with([
            'emetteur:id,name,email',
            'destinataire:id,name,email',
            'concour:id,intitule',
            'emetteur.roles:id,name'
        ]);

        if (!$isSuperAdmin && $isAdmin) {
            $assignedConcoursIds = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->pluck('concour_id');
            $query->whereIn('concour_id', $assignedConcoursIds);
        }

        $messages = $query->where(function ($q) use ($user) {
            $q->where('emetteur_id', $user->id)
                ->orWhere('destinataire_id', $user->id);
        })
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()
            ->reverse();

        $conversations = $messages->groupBy(function ($msg) use ($user, $isStaff) {
            if ($isStaff) {
                $emetteurIsStaff = $msg->emetteur->hasAnyRole(['admin', 'superadmin']);
                return $emetteurIsStaff ? $msg->destinataire_id : $msg->emetteur_id;
            }
            return $user->id;
        });

        $formattedConversations = [];
        $conversationsCount = 0;

        foreach ($conversations as $interlocutorId => $msgs) {
            if ($conversationsCount >= 30) break;

            $limitedMessages = $msgs->take(50);

            $firstMsg = $limitedMessages->first();

            $interlocutorName = $isStaff
                ? ($firstMsg && $firstMsg->emetteur_id == $interlocutorId
                    ? ($firstMsg->emetteur->name ?? 'Inconnu')
                    : ($firstMsg->destinataire->name ?? 'Inconnu'))
                : "Support Administration";

            $formattedConversations[$interlocutorId] = [
                'messages' => $limitedMessages->values(),
                'unread_count' => $msgs->where('destinataire_id', $user->id)->where('lu', 0)->count(),
                'last_message' => $msgs->last(),
                'interlocutor_name' => $interlocutorName
            ];

            $conversationsCount++;
        }

        return response()->json([
            'conversations' => $formattedConversations
        ]);
    }
}
