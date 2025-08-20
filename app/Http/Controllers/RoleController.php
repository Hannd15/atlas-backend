<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    public function deleteRole(int $id) {
        $role = Role::findOrFail($id);
        if (!$role) {
            return response()->json(['error' => 'Rol no encontrado.'], 404);
        }
        $users = $role->users()->get();
        
        // Check if role has users assigned
        if ($users->count() > 0) {
            // Nulls the role_id of the users
            foreach ($users as $user) {
                $user->removeRole($role->name);
            }
        }
        // Detach all permissions associated with the role
        $role->syncPermissions([]);
        $role->delete();
        
        return response()->json(['success' => 'Rol eliminado exitosamente.'], 200);
    }

    public function storeRole(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);
        $role = Role::create([
            'name' => $validated['name'],
        ]);
        
        return response()->json(['success' => 'Rol creado exitosamente.'], 201);
    }
    
    public function updateRole(Request $request, int $id) {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,  
        ]);
        
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['error' => 'Rol no encontrado.'], 404);
        }

        // Update the role
        $role->update([
            'name' => $validated['name'],
        ]);
        
        return response()->json(['success' => 'Rol actualizado exitosamente.'], 200);
    }

    public function getRoles(){
        $roles = Role::orderBy('name')->get();

        return response()->json([
            'roles' => $roles
        ], 200);
    }
    public function getRolePermissions(int $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['error' => 'Rol no encontrado.'], 404);
        }

        $permissions = $role->permissions()->get();

        return response()->json([
            'permissions' => $permissions
        ],200);
    }
    public function assignPermissionToRole(int $permissionId, int $roleId)
    {
        $permission = Permission::find($permissionId);
        if (!$permission) {
            return response()->json(['error' => 'Permiso no encontrado.'], 404);
        }

        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['error' => 'Rol no encontrado.'], 404);
        }

        $role->givePermissionTo($permission);

        return response()->json(['success' => 'Permiso asignado al rol exitosamente.'], 200);
    }
}
