<?php

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
use App\Helpers\TracabiliteHelper;

class BroadcastMessageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) {
            abort(403, 'Accès non autorisé.');
        }

        $concours = collect();

        if ($user->hasRole('superadmin')) {
            $concours = Concour::select('id', 'intitule', 'service_id')->get();
        } elseif ($user->hasRole('gerant')) {
            $service = $user->getService();
            if ($service) {
                $concours = Concour::select('id', 'intitule', 'service_id')
                    ->where('service_id', $service->id)
                    ->get();
            }
        } elseif ($user->hasRole('admin')) {
            $concours = Concour::select('concours.id', 'concours.intitule', 'concours.service_id')
                ->join('concour_admins', 'concours.id', '=', 'concour_admins.concour_id')
                ->where('concour_admins.user_id', $user->id)
                ->get();
        }

        $broadcastsQuery = Message::where('is_broadcast', true)->with(['emetteur', 'concour']);

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

        $concour = Concour::with('service')->findOrFail($request->concour_id);

        if (!$this->canAccessConcour($user, $concour)) {
            abort(403, 'Vous n\'avez pas accès à ce concours.');
        }

        try {
            DB::beginTransaction();

            $candidats = User::whereHas('candidatures', function ($q) use ($request) {
                $q->where('concour_id', $request->concour_id);
            })->get();

            if ($candidats->count() === 0) {
                return back()->with('error', 'Aucun candidat trouvé pour ce concours.');
            }

            $sentCount = 0;
            foreach ($candidats as $candidat) {
                Message::create([
                    'emetteur_id' => $user->id,
                    'destinataire_id' => $candidat->id,
                    'concour_id' => $request->concour_id,
                    'texte' => $request->message,
                    'objet' => $request->subject ?? 'Information du concours',
                    'lu' => 0,
                    'is_broadcast' => true,
                    'broadcast_subject' => $request->subject
                ]);
                $sentCount++;
            }

            DB::commit();

            TracabiliteHelper::log(
                'Diffusion',
                "Diffusion d'un message à {$sentCount} candidats pour le concours « {$concour->intitule} » (Objet: {$request->subject})",
                'broadcast',
                $concour->id,
                null,
                null,
                $concour->service_id,
                $concour->service?->nom
            );

            Log::info("Broadcast envoyé: {$sentCount} candidats notifiés");

            return redirect()->route('broadcast.index')->with('success', "Message diffusé avec succès à {$sentCount} candidats");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur broadcast: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['superadmin', 'admin', 'gerant'])) abort(403);

        $concour = Concour::findOrFail($request->concour_id);

        if (!$this->canAccessConcour($user, $concour)) {
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

    private function canAccessConcour($user, $concour): bool
    {
        if ($user->hasRole('superadmin')) return true;
        if ($user->hasRole('gerant')) {
            $s = $user->getService();
            return $s && $concour->service_id == $s->id;
        }
        if ($user->hasRole('admin')) return DB::table('concour_admins')->where('concour_id', $concour->id)->where('user_id', $user->id)->exists();
        return false;
    }
}
