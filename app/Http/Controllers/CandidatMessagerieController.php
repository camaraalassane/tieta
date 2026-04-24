<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Concour;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CandidatMessagerieController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) return redirect()->route('login');

        $mesConcours = Concour::whereHas('candidatures', function ($q) use ($user) {
            $q->whereHas('profil', function ($sq) use ($user) {
                $sq->where('user_id', $user->id);
            });
        })->get();

        $conversations = Message::where('destinataire_id', $user->id)
            ->orWhere('emetteur_id', $user->id)
            ->with(['emetteur', 'concour'])
            ->oldest()
            ->get()
            ->groupBy(function ($msg) {
                return 'concours_' . $msg->concour_id;
            })
            ->map(function ($msgs) use ($user) {
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
        $destinataire_id = $request->destinataire_id ?? ($concour->user_id ?? 1);

        try {
            $message = Message::create([
                'emetteur_id'     => Auth::id(),
                'destinataire_id' => $destinataire_id,
                'concour_id'      => $request->concour_id,
                'objet'           => 'Question : ' . $concour->intitule,
                'texte'           => $request->texte,
                'lu'              => 0,
            ]);

            // ⭐ NOTIFICATION TEMPS RÉEL - Nouveau message du candidat
            $destinataire = User::find($destinataire_id);
            if ($destinataire) {
                $destinataire->notify(new NewMessageNotification($message, Auth::user()));
                Log::info("Notification de message envoyée au candidat ID: {$destinataire->id}");
            }

            return back()->with('success', 'Message envoyé avec succès');
        } catch (\Exception $e) {
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
