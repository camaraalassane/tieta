<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RoleIndexRequest;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoleController extends Controller implements HasMiddleware
{
   public static function middleware(): array
    {
        return [
            new Middleware('permission:create role')->only(['create', 'store']),
            new Middleware('permission:read role')->only(['index', 'show']),
            new Middleware('permission:update role')->only(['edit', 'update']),
            new Middleware('permission:delete role')->only(['destroy', 'destroyBulk']),
        ];
    }

    public function index(RoleIndexRequest $request)
    {
        $currentUser = Auth::user();

        if (!$currentUser || !$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            abort(403);
        }

        $rolesQuery = Role::query()->with('permissions');

        if ($request->filled('search')) {
            $rolesQuery->where(function($q) use ($request) {
                $q->where('name', 'LIKE', "%" . $request->search . "%")
                  ->orWhere('guard_name', 'LIKE', "%" . $request->search . "%");
            });
        }

        if ($request->filled(['field', 'order'])) {
            $rolesQuery->orderBy((string)$request->field, (string)$request->order);
        }

        if (!$currentUser->hasRole('superadmin')) {
            $rolesQuery->where('name', '<>', 'superadmin');
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $roles */
        $roles = $rolesQuery->paginate(10);
        $roles->withQueryString();

        return Inertia::render('Role/Index', [
            'title'       => "Role",
            'filters'     => $request->all(['search', 'field', 'order']),
            'roles'       => $roles,
            'permissions' => Permission::latest()->get(),
        ]);
    }
    public function store(RoleStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name]);
            $role->givePermissionTo($request->permissions);
            DB::commit();
            return back()->with('success', "Rôle {$role->name} créé.");
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Erreur création : ' .  $th->getMessage());
        }
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        DB::beginTransaction();
        try {
            $role->update(['name' => $request->name]);
            $role->syncPermissions($request->permissions);
            DB::commit();
            return back()->with('success', "Rôle {$role->name} mis à jour.");
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Erreur mise à jour : ' .  $th->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        try {
            $name = $role->name;
            $role->delete();
            return back()->with('success', "Rôle $name supprimé.");
        } catch (\Throwable $th) {
            return back()->with('error', 'Erreur suppression : ' . $th->getMessage());
        }
    }
}