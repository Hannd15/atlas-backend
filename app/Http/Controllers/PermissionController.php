<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="Get all permissions",
     *     tags={"Permissions"},
     *     @OA\Response(response=200, description="List of permissions")
     * )
     */
    public function index()
    {
        try {
            $permissions = Permission::orderBy('name')->get();
            return response()->json([
                'permissions' => $permissions
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching permissions: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al obtener los permisos.'], 500);
        }
    }
}
