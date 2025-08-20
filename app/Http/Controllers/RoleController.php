<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    /**
     * @OA\Delete(
     *     path="/roles/{id}",
     *     summary="Delete a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role deleted"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function destroy(int $id) {
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

    /**
     * @OA\Post(
     *     path="/roles",
     *     summary="Create a new role",
     *     tags={"Roles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Role created")
     * )
     */
    public function create(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);
        $role = Role::create([
            'name' => $validated['name'],
        ]);
        
        return response()->json(['success' => 'Rol creado exitosamente.'], 201);
    }
    
    /**
     * @OA\Put(
     *     path="/roles/{id}",
     *     summary="Update a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role updated"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function update(Request $request, int $id) {
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

    /**
     * @OA\Get(
     *     path="/roles",
     *     summary="Get all roles",
     *     tags={"Roles"},
     *     @OA\Response(response=200, description="List of roles")
     * )
     */
    public function index(){
        $roles = Role::orderBy('name')->get();

        return response()->json([
            'roles' => $roles
        ], 200);
    }
    /**
     * @OA\Get(
     *     path="/roles/{id}/permissions",
     *     summary="Get permissions for a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role permissions"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function getPermissions(int $id)
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
    /**
     * @OA\Post(
     *     path="/roles/{roleId}/permissions/{permissionId}",
     *     summary="Assign a permission to a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission assigned"),
     *     @OA\Response(response=404, description="Role or permission not found")
     * )
     */
    public function assignPermission(int $permissionId, int $roleId)
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
    /**
     * @OA\Delete(
     *     path="/roles/{roleId}/permissions/{permissionId}",
     *     summary="Revoke a permission from a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission revoked"),
     *     @OA\Response(response=404, description="Role or permission not found")
     * )
     */
    public function revokePermission(int $permissionId, int $roleId){
        $permission = Permission::find($permissionId);
        if (!$permission) {
            return response()->json(['error' => 'Permiso no encontrado.'], 404);
        }

        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['error' => 'Rol no encontrado.'], 404);
        }

        $role->revokePermissionTo($permission);

        return response()->json(['success' => 'Permiso revocado del rol exitosamente.'], 200);
    }
}
