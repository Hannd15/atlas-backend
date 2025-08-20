<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);
            $validated['password'] = bcrypt($validated['password']);
            $user = User::create($validated);
            return response()->json(['user' => $user], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al crear el usuario.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get a user by ID",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User found"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener el usuario.'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|required|string|min:8',
            ]);
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }
            $user->update($validated);
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al actualizar el usuario.'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $user->delete();
            return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al eliminar el usuario.'], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/api/users/{id}/roles",
     *     summary="Get roles for a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User roles"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function getRoles(int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $roles = $user->roles()->get();
            return response()->json([
                'roles' => $roles
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user roles: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los roles del usuario.'], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/users/{id}/permissions",
     *     summary="Get permissions for a user",
     *     tags={"Users"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User permissions"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function getPermissions(int $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $permissions = $user->getAllPermissions();
            return response()->json([
                'permissions' => $permissions
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user permissions: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los permisos del usuario.'], 500);
        }
    }

        /**
         * @OA\Get(
         *     path="/api/users",
         *     summary="Get all users",
         *     tags={"Users"},
         *     @OA\Response(response=200, description="List of users")
         * )
         */
    public function index()
    {
        try {
            $users = User::all();
            return response()->json([
                'users' => $users
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los usuarios.'], 500);
        }
    }

        /**
         * @OA\Post(
         *     path="/api/users/{userId}/permissions/{permissionId}",
         *     summary="Assign a permission to a user",
         *     tags={"Users"},
         *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
         *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
         *     @OA\Response(response=200, description="Permission assigned"),
         *     @OA\Response(response=404, description="User or permission not found")
         * )
         */
    public function assignPermission(int $userId, int $permissionId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $permission = Permission::find($permissionId);
            if (!$permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }
            $user->givePermissionTo($permission);
            return response()->json(['success' => 'Permiso asignado al usuario exitosamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error assigning permission to user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al asignar el permiso al usuario.'], 500);
        }
    }

        /**
         * @OA\Delete(
         *     path="/api/users/{userId}/permissions/{permissionId}",
         *     summary="Revoke a permission from a user",
         *     tags={"Users"},
         *     @OA\Parameter(name="userId", in="path", required=true, @OA\Schema(type="integer")),
         *     @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer")),
         *     @OA\Response(response=200, description="Permission revoked"),
         *     @OA\Response(response=404, description="User or permission not found")
         * )
         */
    public function revokePermission(int $userId, int $permissionId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $permission = Permission::find($permissionId);
            if (!$permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }
            $user->revokePermissionTo($permission);
            return response()->json(['success' => 'Permiso revocado del usuario exitosamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error revoking permission from user: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al revocar el permiso del usuario.'], 500);
        }
    }
}
