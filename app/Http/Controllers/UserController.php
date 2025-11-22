<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/users",
     *     summary="Get all users",
     *     description="Retrieve a list of all users with their assigned roles",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of users retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los usuarios.")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $users = User::with('roles')
                ->orderByDesc('updated_at')
                ->get()
                ->map(function ($user) {
                    $rolesList = $user->roles->pluck('name')->implode(', ');

                    $item = $user->toArray();
                    $item['roles_list'] = $rolesList;
                    // remove relation data to avoid exposing pivot tables
                    unset($item['roles']);

                    return $item;
                });

            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los usuarios.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/users",
     *     summary="Create a new user",
     *     description="Create a new user with optional role assignments",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data",
     *
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="User's full name (max 255 characters)",
     *                 example="John Doe"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="User's email address (must be unique, max 255 characters)",
     *                 example="john@example.com"
     *             ),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="Array of role IDs to assign to the user",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
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
     *             @OA\Property(property="message", type="string", example="The email has already been taken."),
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al crear el usuario.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'roles' => 'nullable|array',
                'roles.*' => 'integer|exists:roles,id',
            ]);
            $validated['password'] = bcrypt($validated['password']);
            $user = User::create($validated);

            if (isset($validated['roles'])) {
                $user->assignRole(Role::whereIn('id', $validated['roles'])->get());
            }

            $user->refresh();
            $rolesList = $user->roles->pluck('name')->implode(', ');

            $item = $user->toArray();
            $item['roles_list'] = $rolesList;
            unset($item['roles']);

            return response()->json($item, 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al crear el usuario.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/users/{id}",
     *     summary="Get a user by ID",
     *     description="Retrieve a single user with their assigned roles",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(
     *                 property="roles_list",
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
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Usuario no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener el usuario.")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        try {
            $user = User::with('roles')->find($id);
            if (! $user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $item = $this->formatUserForResponse($user);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener el usuario.'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/users/batch",
     *     summary="Get multiple users by ID",
     *     description="Retrieve multiple users with their assigned roles",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array of user IDs",
     *
     *         @OA\JsonContent(
     *             required={"ids"},
     *
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="roles_list",
     *                     type="array",
     *                     @OA\Items(type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="One or more users not found",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Usuario no encontrado."),
     *             @OA\Property(property="missing_ids", type="array", @OA\Items(type="integer", example=5))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los usuarios.")
     *         )
     *     )
     * )
     */
    public function batchShow(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|min:1',
            ]);

            $ids = array_values($validated['ids']);
            $users = User::with('roles')->whereIn('id', $ids)->get()->keyBy('id');
            $foundIds = $users->keys()->map(fn ($value) => (int) $value)->all();
            $missing = array_values(array_diff($ids, $foundIds));

            if (! empty($missing)) {
                return response()->json([
                    'error' => 'Usuario no encontrado.',
                    'missing_ids' => $missing,
                ], 404);
            }

            $items = array_map(function (int $id) use ($users) {
                return $this->formatUserForResponse($users->get($id));
            }, $ids);

            return response()->json($items, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users batch: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los usuarios.'], 500);
        }
    }

    private function formatUserForResponse(User $user): array
    {
        $rolesList = $user->roles->pluck('id')->map(fn ($id) => (int) $id);

        $item = $user->toArray();
        $item['roles_list'] = $rolesList;
        unset($item['roles']);

        return $item;
    }

    /**
     * @OA\Put(
     *     path="/api/auth/users/{id}",
     *     summary="Update a user",
     *     description="Update an existing user's information and/or role assignments",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data to update (all fields are optional)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="User's full name (max 255 characters)",
     *                 example="Jane Doe"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="User's email address (must be unique, max 255 characters)",
     *                 example="jane@example.com"
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 description="User's new password (minimum 8 characters)",
     *                 example="newpassword123"
     *             ),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="Array of role IDs to sync with the user",
     *
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
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
     *             @OA\Property(property="message", type="string", example="The email has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Usuario no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al actualizar el usuario.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = User::find($id);
            if (! $user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$id,
                'password' => 'sometimes|required|string|min:8',
                'roles' => 'nullable|array',
                'roles.*' => 'integer|exists:roles,id',
            ]);
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }
            $user->update($validated);

            if (isset($validated['roles'])) {
                $user->syncRoles(Role::whereIn('id', $validated['roles'])->get());
            }

            $user->refresh();
            $rolesList = $user->roles->pluck('name')->implode(', ');

            $item = $user->toArray();
            $item['roles_list'] = $rolesList;
            unset($item['roles']);

            return response()->json($item, 200);
        } catch (\Exception $e) {
            Log::error('Error updating user: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al actualizar el usuario.'], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/auth/users/{id}",
     *     summary="Delete a user",
     *     description="Remove a user from the system",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Usuario eliminado correctamente.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="Usuario no encontrado.")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al eliminar el usuario.")
     *         )
     *     )
     * )
     */
    public function destroy(int $id)
    {
        try {
            $user = User::find($id);
            if (! $user) {
                return response()->json(['error' => 'Usuario no encontrado.'], 404);
            }
            $user->delete();

            return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting user: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al eliminar el usuario.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/auth/users/dropdown",
     *     summary="Get users for dropdown",
     *     description="Retrieve all users formatted for dropdown/select components",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of users for dropdown retrieved successfully",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *
     *                 @OA\Property(property="value", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="John Doe")
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
     *             @OA\Property(property="error", type="string", example="Ocurrió un error al obtener los usuarios.")
     *         )
     *     )
     * )
     */
    public function dropdown()
    {
        try {
            $users = User::all()->map(function ($user) {
                return ['value' => $user->id, 'label' => $user->name];
            });

            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching users for dropdown: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['error' => 'Ocurrió un error al obtener los usuarios.'], 500);
        }
    }
}
