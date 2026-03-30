<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Concour;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConcourMessagerieController extends Controller
{
public function index(Request $request)
{
    $user = Auth::user();

    // 1. Sécurité : Vérification d'accès rapide
    if (!$user->hasAnyRole(['superadmin', 'admin'])) {
        abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
    }

    $isSuperAdmin = $user->hasRole('superadmin');
    $isAdmin = $user->hasRole('admin');
    $isStaff = $isSuperAdmin || $isAdmin;

    // 2. Query optimisée avec chargement groupé des relations (Eager Loading)
    // On ne récupère que les 500 derniers messages pour éviter de charger 50 000 lignes
    $query = Message::with([
        'emetteur:id,name,email', 
        'destinataire:id,name,email', 
        'concour:id,intitule',
        'emetteur.roles:id,name' // On récupère uniquement le nom des rôles
    ]);

    // 3. Filtrage selon les droits
    if (!$isSuperAdmin && $isAdmin) {
        // Un Admin ne voit que les concours assignés
        $assignedConcoursIds = DB::table('concour_admins')
            ->where('user_id', $user->id)
            ->pluck('concour_id');

        $query->whereIn('concour_id', $assignedConcoursIds);
    } elseif (!$isStaff) {
        // Un Candidat ne voit que ses échanges (Sécurité supplémentaire)
        $query->where(function($q) use ($user) {
            $q->where('emetteur_id', $user->id)->orWhere('destinataire_id', $user->id);
        });
    }

    // 4. Récupération des données limitées et triées
    // Récupérer les 300 derniers messages est suffisant pour l'affichage initial
    $messages = $query->latest()->limit(300)->get()->reverse();

    // 5. Groupement des messages en conversations (Logique PHP)
    $conversations = $messages->groupBy(function($msg) use ($user, $isStaff) {
        if ($isStaff) {
            // Pour le staff, on groupe par le contact externe (celui qui n'est pas nous)
            // Si l'émetteur a un rôle staff, l'interlocuteur est le destinataire, sinon c'est l'émetteur
            $emetteurIsStaff = $msg->emetteur->hasAnyRole(['admin', 'superadmin']);
            return $emetteurIsStaff ? $msg->destinataire_id : $msg->emetteur_id;
        }
        return $user->id; // Pour un candidat, tout est dans un seul groupe
    });

    // 6. Formatage pour Inertia
    $formattedConversations = $conversations->map(function($msgs) use ($user) {
        return [
            'messages' => $msgs->values(),
            'unread_count' => $msgs->where('destinataire_id', $user->id)->where('lu', 0)->count(),
            'last_message' => $msgs->last()
        ];
    });

    return Inertia::render('Concours/Messagerie/index', [
        'conversations' => $formattedConversations,
        // OPTIMISATION CRUCIALE : On ne charge PLUS 50 000 users ici.
        // On renvoie une liste vide. La recherche devra se faire via une API séparée ou au clic.
        'users' => [], 
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

        // Sécurité : Vérifier si l'admin (non superadmin) a le droit d'écrire pour ce concours
        if ($user->hasRole('admin') && !$user->hasRole('superadmin')) {
            $hasAccess = DB::table('concour_admins')
                ->where('user_id', $user->id)
                ->where('concour_id', $request->concour_id)
                ->exists();

            if (!$hasAccess) {
                abort(403, "Vous n'êtes pas autorisé à envoyer des messages pour ce concours.");
            }
        }

        Message::create([
            'emetteur_id' => $user->id,
            'destinataire_id' => $request->destinataire_id, 
            'concour_id' => $request->concour_id,
            'texte' => $request->texte,
            'objet' => 'Discussion Concours', 
            'lu' => 0,
        ]);

        return back();
    }

    public function markAsRead($interlocuteurId)
    {
        Message::where('destinataire_id', Auth::id())
            ->where('emetteur_id', $interlocuteurId)
            ->where('lu', 0)
            ->update(['lu' => 1]);

        return back();
    }
}