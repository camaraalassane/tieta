<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PermissionIndexRequest;
use App\Http\Requests\PermissionStoreRequest;
use App\Http\Requests\PermissionUpdateRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PermissionController extends Controller implements HasMiddleware
{
    /**
     * Correction P1013 : Définition statique des middlewares (Standard Laravel 12)
     */
    public static function middleware(): array
    {
        return [
            (new Middleware('permission:create permission'))->only(['create', 'store']),
            (new Middleware('permission:read permission'))->only(['index', 'show']),
            (new Middleware('permission:update permission'))->only(['edit', 'update']),
            (new Middleware('permission:delete permission'))->only(['destroy', 'destroyBulk']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PermissionIndexRequest $request)
    {
        // Correction Intelephense : Utilisation de Auth::user()
        $currentUser = Auth::user();

        // BLOQUER L'ACCÈS
        if (!$currentUser || !$currentUser->hasAnyRole(['superadmin', 'admin'])) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        $permissionsQuery = Permission::query();

        if ($request->filled('search')) {
            $permissionsQuery->where(function($q) use ($request) {
                $q->where('name', 'LIKE', "%" . $request->search . "%")
                  ->orWhere('guard_name', 'LIKE', "%" . $request->search . "%");
            });
        }

        if ($request->filled(['field', 'order'])) {
            $permissionsQuery->orderBy((string)$request->field, (string)$request->order);
        }

        // Correction P1013 : On type la pagination pour withQueryString
        /** @var \Illuminate\Pagination\LengthAwarePaginator $permissions */
        $permissions = $permissionsQuery->paginate(10);
        $permissions->withQueryString();

        return Inertia::render('Permission/Index', [
            'title'       => 'Permission',
            'filters'     => $request->all(['search', 'field', 'order']),
            'permissions' => $permissions,
        ]);
    }

    public function store(PermissionStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $permission = Permission::create([
                'name' => $request->name
            ]);
            
            $superadmin = Role::whereName('superadmin')->first();
            if ($superadmin) {
                $superadmin->givePermissionTo([$request->name]);
            }

            DB::commit();
            return back()->with('success', $permission->name. ' created successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error creating : ' . $th->getMessage());
        }
    }

    public function update(PermissionUpdateRequest $request, Permission $permission)
    {
        DB::beginTransaction();
        try {
            $superadmin = Role::whereName('superadmin')->first();
            
            // On révoque l'ancien nom et on donne le nouveau au superadmin
            if ($superadmin) {
                $superadmin->revokePermissionTo([$permission->name]);
            }

            $permission->update([
                'name' => $request->name
            ]);

            if ($superadmin) {
                $superadmin->givePermissionTo([$permission->name]);
            }

            DB::commit();
            return back()->with('success', $permission->name. ' updated successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error updating : ' . $th->getMessage());
        }
    }

    public function destroy(Permission $permission)
    {
        DB::beginTransaction();
        try {
            $permissionName = $permission->name;
            $superadmin = Role::whereName('superadmin')->first();
            
            if ($superadmin) {
                $superadmin->revokePermissionTo([$permissionName]);
            }

            $permission->delete();
            
            DB::commit();
            return back()->with('success', $permissionName . ' deleted successfully.');
        } catch (\Throwable $th) {
            DB::rollback();
            return back()->with('error', 'Error deleting : ' . $th->getMessage());
        }
    }
}