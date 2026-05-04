<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserIndexRequest;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Helpers\TracabiliteHelper;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:create user'))->only(['create', 'store']),
            (new Middleware('permission:read user'))->only(['index', 'show']),
            (new Middleware('permission:update user'))->only(['edit', 'update']),
            (new Middleware('permission:delete user'))->only(['destroy', 'destroyBulk']),
        ];
    }

    public function index(UserIndexRequest $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $usersQuery = User::query()->with('roles');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $usersQuery->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('prenom', 'ILIKE', "%{$searchTerm}%")
                    ->orWhereRaw("CONCAT(name, ' ', COALESCE(prenom, '')) ILIKE ?", ["%{$searchTerm}%"]);
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $usersQuery->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled(['field', 'order'])) {
            $field = $request->field;
            $order = $request->order;
            if ($field === 'prenom') {
                $usersQuery->orderByRaw("CASE WHEN prenom IS NULL OR prenom = '' THEN 1 ELSE 0 END, prenom {$order}");
            } else {
                $usersQuery->orderBy($field, $order);
            }
        }

        $userRole = $currentUser->roles->pluck('name')[0] ?? null;
        $roles = Role::all();

        if ($userRole !== 'superadmin') {
            $usersQuery->whereHas('roles', function ($query) {
                $query->where('name', '<>', 'superadmin');
            });
            $roles = Role::where('name', '<>', 'superadmin')->get();
        }

        $users = $usersQuery->paginate(10);
        $users->withQueryString();

        return Inertia::render('User/Index', [
            'title' => 'User',
            'filters' => $request->all(['search', 'field', 'order', 'role']),
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($request->role);
            DB::commit();

            TracabiliteHelper::log(
                'Création',
                "Création de l'utilisateur « {$user->name} » avec le rôle « {$request->role} »",
                'user',
                $user->id,
                null,
                $user->toArray()
            );

            return back()->with('success', "L'utilisateur {$user->name} a été créé.");
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Erreur lors de la création : ' . $th->getMessage());
        }
    }

    public function update(UserUpdateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);

            $donneesAvant = $user->toArray();
            $anciensRoles = $user->getRoleNames()->toArray();

            $user->update([
                'name' => $request->name,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);
            $user->syncRoles($request->role);
            DB::commit();

            $nouveauxRoles = $user->getRoleNames()->toArray();
            $desc = "Modification de l'utilisateur « {$user->name} »";
            if ($anciensRoles !== $nouveauxRoles) {
                $desc .= " - Rôle changé de « " . implode(', ', $anciensRoles) . " » à « " . implode(', ', $nouveauxRoles) . " »";
            }

            TracabiliteHelper::log(
                'Modification',
                $desc,
                'user',
                $user->id,
                $donneesAvant,
                $user->fresh()->toArray()
            );

            return back()->with('success', "L'utilisateur {$user->name} a été mis à jour.");
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Erreur lors de la mise à jour : ' . $th->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            $userData = $user->toArray();
            $name = $user->name;
            $user->delete();

            TracabiliteHelper::log(
                'Suppression',
                "Suppression de l'utilisateur « {$name} »",
                'user',
                $userData['id'],
                $userData,
                null
            );

            return back()->with('success', "L'utilisateur $name a été supprimé.");
        } catch (\Throwable $th) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $th->getMessage());
        }
    }
}
