<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/permissions",
     *     summary="Get all permissions",
     *     description="Retrieve a list of all permissions with their associated roles",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of permissions retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="edit-posts"),
     *                 @OA\Property(property="guard_name", type="string", example="web"),
     *                 @OA\Property(property="roles_list", type="string", example="Admin, Editor"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los permisos.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $permissions = Permission::with('roles')->get()->map(function ($permission) {
                $permission->roles_list = $permission->roles->pluck('name')->implode(', ');

                $item = $permission->toArray();
                // remove relation data to avoid exposing pivot tables
                unset($item['roles']);

                return $item;
            });

            return response()->json($permissions, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los permisos.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/permissions/{id}",
     *     summary="Get a permission by ID",
     *     description="Retrieve a single permission with its associated roles",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Permission ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="edit-posts"),
     *             @OA\Property(property="guard_name", type="string", example="web"),
     *             @OA\Property(property="roles_list", type="array", @OA\Items(
     *                 @OA\Property(property="value", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="Admin")
     *             )),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Permiso no encontrado.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener el permiso.")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        try {
            $permission = Permission::with('roles')->find($id);
            if (! $permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }
            $permission->roles_list = $permission->roles->map(function ($role) {
                return ['value' => $role->id, 'label' => $role->name];
            });

            $item = $permission->toArray();
            unset($item['roles']);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching permission: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener el permiso.'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/auth/permissions/{id}",
     *     summary="Update a permission",
     *     description="Update the name of an existing permission",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Permission ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permission data to update",
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Permission name (must be unique, max 255 characters)",
     *                 example="edit-posts"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="edit-posts"),
     *             @OA\Property(property="guard_name", type="string", example="web"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The name field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Permiso no encontrado.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al actualizar el permiso.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $permission = Permission::find($id);
            if (! $permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name,'.$id,
            ]);

            $permission->update(['name' => $validated['name']]);

            $permission->refresh();
            $item = $permission->toArray();
            unset($item['roles']);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error updating permission: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al actualizar el permiso.'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/permissions/{id}",
     *     summary="Delete a permission",
     *     description="Remove a permission from the system",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Permission ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Permiso eliminado correctamente.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Permiso no encontrado.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al eliminar el permiso.")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $permission = Permission::find($id);
            if (! $permission) {
                return response()->json(['error' => 'Permiso no encontrado.'], 404);
            }
            $permission->delete();

            return response()->json(['message' => 'Permiso eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting permission: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al eliminar el permiso.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/permissions/dropdown",
     *     summary="Get permissions for dropdown",
     *     description="Retrieve all permissions formatted for dropdown/select components",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of permissions for dropdown retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="value", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="edit-posts")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los permisos.")
     *         )
     *     )
     * )
     */
    public function dropdown()
    {
        try {
            $permissions = Permission::all()->map(function ($permission) {
                return ['value' => $permission->id, 'label' => $permission->name];
            });

            return response()->json($permissions, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions for dropdown: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los permisos.'], 500);
        }
    }
}
