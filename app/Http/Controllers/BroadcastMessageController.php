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

        // ⭐ Vérification des droits avec les rôles (superadmin, admin, gerant)
        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        // ⭐ Récupération des concours accessibles selon le rôle
        $concours = collect();

        if ($user->hasRole('superadmin')) {
            // Superadmin voit tous les concours
            $concours = Concour::select('id', 'intitule', 'service_id')->get();
        } elseif ($user->hasRole('gerant')) {
            // Gérant voit les concours de son service uniquement
            $service = $user->getService();
            if ($service) {
                $concours = Concour::select('id', 'intitule', 'service_id')
                    ->where('service_id', $service->id)
                    ->get();
            }
        } elseif ($user->hasRole('admin')) {
            // Admin voit les concours où il est assigné (via concour_admins)
            $concours = Concour::select('concours.id', 'concours.intitule', 'concours.service_id')
                ->join('concour_admins', 'concours.id', '=', 'concour_admins.concour_id')
                ->where('concour_admins.user_id', $user->id)
                ->get();
        }

        // ⭐ Récupération de l'historique des messages broadcast accessibles
        $broadcastsQuery = Message::where('is_broadcast', true)
            ->with(['emetteur', 'concour']);

        if (!$user->hasRole('superadmin')) {
            if ($user->hasRole('gerant')) {
                $service = $user->getService();
                if ($service) {
                    $broadcastsQuery->whereHas('concour', function ($q) use ($service) {
                        $q->where('service_id', $service->id);
                    });
                } else {
                    $broadcastsQuery->whereRaw('1 = 0');
                }
            } elseif ($user->hasRole('admin')) {
                $broadcastsQuery->whereHas('concour', function ($q) use ($user) {
                    $q->whereHas('admins', function ($sub) use ($user) {
                        $sub->where('user_id', $user->id);
                    });
                });
            }
        }

        $broadcasts = $broadcastsQuery->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Concours/BroadcastMessage', [
            'concours' => $concours,
            'broadcasts' => $broadcasts,
            'user_role' => $user->getRoleNames()->first(),
            'is_superadmin' => $user->hasRole('superadmin'),
            'is_gerant' => $user->hasRole('gerant'),
            'is_admin' => $user->hasRole('admin'),
        ]);
    }

    public function send(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:3'
        ]);

        // ⭐ Vérifier que l'utilisateur a accès à ce concours
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
            $isAssigned = DB::table('concour_admins')
                ->where('concour_id', $concour->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            abort(403, 'Vous n\'avez pas accès à ce concours.');
        }

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

                $candidat->notify(new NewMessageNotification($message, $user));
                $sentCount++;
            }

            DB::commit();

            Log::info("Broadcast envoyé: {$sentCount} candidats notifiés pour le concours ID: {$request->concour_id} par {$user->email}");

            $successMessage = "Message diffusé avec succès à {$sentCount} candidats";

            return redirect()->route('broadcast.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'envoi du broadcast: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $concour = Concour::findOrFail($request->concour_id);

        // ⭐ Vérifier que l'utilisateur a accès à ce concours
        $hasAccess = false;

        if ($user->hasRole('superadmin')) {
            $hasAccess = true;
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service && $concour->service_id == $service->id) {
                $hasAccess = true;
            }
        } elseif ($user->hasRole('admin')) {
            $isAssigned = DB::table('concour_admins')
                ->where('concour_id', $concour->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isAssigned) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }

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
