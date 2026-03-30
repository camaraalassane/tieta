<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Concour;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CandidatMessagerieController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Sécurité si non connecté
        if (!$user) return redirect()->route('login');

        // Récupération des concours auxquels le candidat a postulé
        $mesConcours = Concour::whereHas('candidatures', function($q) use ($user) {
    // Si la relation profil existe, Laravel comparera l'ID automatiquement
    $q->whereHas('profil', function($sq) use ($user) {
        $sq->where('user_id', $user->id);
    });
})->get();

        // Groupement des messages par conversation (concours)
        $conversations = Message::where('destinataire_id', $user->id)
            ->orWhere('emetteur_id', $user->id)
            ->with(['emetteur', 'concour']) 
            ->oldest()
            ->get()
            ->groupBy(function($msg) {
                return 'concours_' . $msg->concour_id;
            })
            ->map(function($msgs) use ($user) {
                return [
                    'messages' => $msgs,
                    'unread_count' => $msgs->where('destinataire_id', $user->id)->where('lu', 0)->count()
                ];
            });

        return Inertia::render('Candidat/Messagerie', [
            'conversations' => $conversations,
            'mesConcours' => $mesConcours
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'texte' => 'required|string',
            'concour_id' => 'required|exists:concours,id',
        ]);

        $concour = Concour::findOrFail($request->concour_id);

        // Définition du destinataire (Admin lié au concours ou ID 1 par défaut)
        $destinataire_id = $request->destinataire_id ?? ($concour->user_id ?? 1);

        try {
            Message::create([
                // Correction P1013 : Utilisation de la façade Auth
                'emetteur_id'     => Auth::id(),
                'destinataire_id' => $destinataire_id, 
                'concour_id'      => $request->concour_id,
                'objet'           => 'Question : ' . $concour->intitule,
                'texte'           => $request->texte,
                'lu'              => 0,
            ]);

            return back();

        } catch (\Exception $e) {
            // Debug utile en phase de développement
            return back()->withErrors([
                'error' => "Erreur lors de l'envoi : " . $e->getMessage()
            ]);
        }
    }

    public function markAsRead($concourId)
    {
        Message::where('concour_id', $concourId)
            ->where('destinataire_id', Auth::id())
            ->where('lu', 0)
            ->update(['lu' => 1]);

        return back();
    }
}