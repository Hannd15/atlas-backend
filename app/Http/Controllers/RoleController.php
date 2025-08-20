<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role deleted"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function destroy(int $id) {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al eliminar el rol.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
            ]);
            $role = Role::create([
                'name' => $validated['name'],
            ]);
            return response()->json(['success' => 'Rol creado exitosamente.'], 201);
        } catch (\Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al crear el rol.'], 500);
        }
    }
    
    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
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
        try {
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
        } catch (\Exception $e) {
            Log::error('Error updating role: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al actualizar el rol.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get all roles",
     *     tags={"Roles"},
     *     @OA\Response(response=200, description="List of roles")
     * )
     */
    public function index(){
        try {
            $roles = Role::orderBy('name')->get();
            return response()->json([
                'roles' => $roles
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching roles: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los roles.'], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/roles/{id}/permissions",
     *     summary="Get permissions for a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role permissions"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    public function getPermissions(int $id)
    {
        try {
            $role = Role::find($id);
            if (!$role) {
                return response()->json(['error' => 'Rol no encontrado.'], 404);
            }
            $permissions = $role->permissions()->get();
            return response()->json([
                'permissions' => $permissions
            ],200);
        } catch (\Exception $e) {
            Log::error('Error fetching role permissions: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los permisos del rol.'], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/roles/{roleId}/permissions/{permissionId}",
     *     summary="Assign a permission to a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission assigned"),
     *     @OA\Response(response=404, description="Role or permission not found")
     * )
     */
    public function assignPermission(int $roleId, int $permissionId)
    {
        try {
            $permission = Permission::find($permissionId);
            if (!$permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }
            Log::debug($roleId);
            $role = Role::find($roleId);
            if (!$role) {
                return response()->json(['error' => 'Rol no encontrado.'], 404);
            }
            $role->givePermissionTo($permission);
            return response()->json(['success' => 'Permiso asignado al rol exitosamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error assigning permission to role: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al asignar el permiso al rol.'], 500);
        }
    }
    /**
     * @OA\Delete(
     *     path="/api/roles/{roleId}/permissions/{permissionId}",
     *     summary="Revoke a permission from a role",
     *     tags={"Roles"},
     *     @OA\Parameter(name="roleId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Permission revoked"),
     *     @OA\Response(response=404, description="Role or permission not found")
     * )
     */
    public function revokePermission(int $roleId, int $permissionId){
        try {
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
        } catch (\Exception $e) {
            Log::error('Error revoking permission from role: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al revocar el permiso del rol.'], 500);
        }
    }
}
