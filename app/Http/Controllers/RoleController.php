<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/roles",
     *     summary="Get all roles",
     *     description="Retrieve a list of all roles with their associated permissions",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of roles retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Admin"),
     *                 @OA\Property(property="guard_name", type="string", example="web"),
     *                 @OA\Property(property="permissions_list", type="string", example="edit-posts, delete-posts"),
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los roles.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $roles = Role::with('permissions')
                ->orderByDesc('updated_at')
                ->get()
                ->map(function ($role) {
                    $permissionsList = $role->permissions->pluck('name')->implode(', ');

                    $item = $role->toArray();
                    $item['permissions_list'] = $permissionsList;
                    // remove relation data to avoid exposing pivot tables
                    unset($item['permissions']);

                    return $item;
                });

            return response()->json($roles, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching roles: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los roles.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/roles",
     *     summary="Create a new role",
     *     description="Create a new role with optional permissions",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role data",
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Role name (must be unique, max 255 characters)",
     *                 example="Editor"
     *             ),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 description="Array of permission IDs to assign to the role",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Editor"),
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
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al crear el rol.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id',
            ]);

            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

            if (isset($validated['permissions'])) {
                $role->givePermissionTo(Permission::whereIn('id', $validated['permissions'])->get());
            }

            $role->refresh();
            $permissionsList = $role->permissions->pluck('name')->implode(', ');

            $item = $role->toArray();
            $item['permissions_list'] = $permissionsList;
            unset($item['permissions']);

            return response()->json($item, 201);
        } catch (\Exception $e) {
            Log::error('Error creating role: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al crear el rol.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/roles/{id}",
     *     summary="Get a role by ID",
     *     description="Retrieve a single role with its associated permissions",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Role ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Role retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Admin"),
     *             @OA\Property(property="guard_name", type="string", example="web"),
     *             @OA\Property(
     *                 property="permissions_list",
     *                 type="array",
     *
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Rol no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener el rol.")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        try {
            $role = Role::with('permissions')->find($id);
            if (! $role) {
                return response()->json(['error' => 'Rol no encontrado.'], 404);
            }
            $permissionsList = $role->permissions->pluck('id')->map(fn ($id) => (int) $id);

            $item = $role->toArray();
            $item['permissions_list'] = $permissionsList;
            unset($item['permissions']);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching role: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener el rol.'], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/auth/roles/{id}",
     *     summary="Update a role",
     *     description="Update an existing role's name and/or permissions",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Role ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role data to update (all fields are optional)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Role name (must be unique, max 255 characters)",
     *                 example="Super Admin"
     *             ),
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 description="Array of permission IDs to sync with the role",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Super Admin"),
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
     *             @OA\Property(property="message", type="string", example="The name has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Rol no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al actualizar el rol.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $role = Role::find($id);
            if (! $role) {
                return response()->json(['error' => 'Rol no encontrado.'], 404);
            }
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:roles,name,'.$id,
                'permissions' => 'nullable|array',
                'permissions.*' => 'integer|exists:permissions,id',
            ]);

            if (isset($validated['name'])) {
                $role->update(['name' => $validated['name']]);
            }

            if (isset($validated['permissions'])) {
                $role->syncPermissions(Permission::whereIn('id', $validated['permissions'])->get());
            }

            $role->refresh();
            $permissionsList = $role->permissions->pluck('name')->implode(', ');

            $item = $role->toArray();
            $item['permissions_list'] = $permissionsList;
            unset($item['permissions']);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error updating role: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al actualizar el rol.'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/roles/{id}",
     *     summary="Delete a role",
     *     description="Remove a role from the system",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Role ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Role deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Rol eliminado correctamente.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Rol no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al eliminar el rol.")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $role = Role::find($id);
            if (! $role) {
                return response()->json(['error' => 'Rol no encontrado.'], 404);
            }
            $role->delete();

            return response()->json(['message' => 'Rol eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting role: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al eliminar el rol.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/roles/dropdown",
     *     summary="Get roles for dropdown",
     *     description="Retrieve all roles formatted for dropdown/select components",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of roles for dropdown retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="value", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="Admin")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los roles.")
     *         )
     *     )
     * )
     */
    public function dropdown()
    {
        try {
            $roles = Role::all()->map(function ($role) {
                return ['value' => $role->id, 'label' => $role->name];
            });

            return response()->json($roles, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching roles for dropdown: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los roles.'], 500);
        }
    }
}
