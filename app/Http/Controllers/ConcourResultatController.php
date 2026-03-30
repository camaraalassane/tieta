<?php
namespace App\Http\Controllers;

use App\Models\Concour; // Vérifiez l'orthographe (Concour ou Concours)
use App\Models\Resultat; // Supposons que votre modèle s'appelle Resultat
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
class ConcourResultatController extends Controller
{
    /**
     * Affiche la page de création avec la liste des concours
     */
    public function index(Request $request)

    {
        $user = $request->user();
            // BLOQUER L'ACCÈS : Doit être admin ou superadmin
    if (!$user->hasAnyRole(['superadmin', 'admin'])) {
        abort(403);
    }
        return Inertia::render('Concours/creerResultat', [
            'concours' => Concour::select('id', 'intitule as nom')->get()
        ]);
    }

    /**
     * Vérifie dynamiquement l'existence d'un résultat (Appelé par Vue)
     */
    public function checkExistance($concoursId)
    {


        // Remplacez 'Resultat' par votre modèle réel (ex: ConcourResultat)
        $exists = Resultat::where('concour_id', $concoursId)->first();

        return response()->json([
            'exists' => $exists ? true : false,
            'resultat_id' => $exists ? $exists->id : null
        ]);
    }

    /**
     * Enregistre le nouveau résultat
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'concour_id' => 'required|exists:concours,id',
            'intitule' => 'required|string|max:255',
            'statut' => 'required|string'
        ]);

        // Création du résultat
        Resultat::create([
            'concour_id' => $request->concour_id,
            'intitule' => $request->intitule,
            'statut' => $request->statut,
        ]);

        return redirect()->back()->with('success', 'Résultat créé avec succès.');
    }
}
