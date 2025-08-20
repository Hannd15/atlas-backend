<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/permissions",
     *     summary="Get all permissions",
     *     tags={"Permissions"},
     *     @OA\Response(response=200, description="List of permissions")
     * )
     */
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        return response()->json([
            'permissions' => $permissions
        ], 200);
    }
}
