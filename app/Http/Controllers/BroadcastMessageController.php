<?php
// app/Http/Controllers/BroadcastMessageController.php

namespace App\Http\Controllers;

use App\Models\Concour;
use App\Models\User;
use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BroadcastMessageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        // Récupérer les concours accessibles
        $query = Concour::query();

        if (!$user->hasRole('superadmin')) {
            $query->whereHas('admins', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $concours = $query->get(['id', 'intitule']);

        // Récupérer l'historique des messages broadcast
        $broadcasts = Message::where('is_broadcast', true)
            ->with(['emetteur', 'concour'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Concours/BroadcastMessage', [
            'concours' => $concours,
            'broadcasts' => $broadcasts
        ]);
    }

    public function send(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:3'
        ]);

        try {
            DB::beginTransaction();

            // Récupérer tous les candidats du concours
            $candidats = User::whereHas('candidatures', function ($q) use ($request) {
                $q->where('concour_id', $request->concour_id);
            })->get();

            if ($candidats->count() === 0) {
                return back()->with('error', 'Aucun candidat trouvé pour ce concours.');
            }

            $sentCount = 0;
            foreach ($candidats as $candidat) {
                // Créer un message pour chaque candidat
                $message = Message::create([
                    'emetteur_id' => $user->id,
                    'destinataire_id' => $candidat->id,
                    'concour_id' => $request->concour_id,
                    'texte' => $request->message,
                    'objet' => $request->subject ?? 'Information du concours',
                    'lu' => 0,
                    'is_broadcast' => true,
                    'broadcast_subject' => $request->subject
                ]);

                // Envoyer la notification temps réel
                $candidat->notify(new NewMessageNotification($message, $user));
                $sentCount++;
            }

            DB::commit();

            Log::info("Broadcast envoyé: {$sentCount} candidats notifiés pour le concours ID: {$request->concour_id}");

            return redirect()->route('broadcast.index')->with(
                'success',
                "Message diffusé avec succès à {$sentCount} candidats"
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'envoi du broadcast: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $concour = Concour::findOrFail($request->concour_id);
        $candidatsCount = User::whereHas('candidatures', function ($q) use ($request) {
            $q->where('concour_id', $request->concour_id);
        })->count();

        return response()->json([
            'concour' => $concour->intitule,
            'candidats_count' => $candidatsCount,
            'message' => $request->message,
            'subject' => $request->subject
        ]);
    }
}
