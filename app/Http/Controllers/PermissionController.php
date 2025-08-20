<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function getPermissions()
    {
        $permissions = Permission::orderBy('name')->get();
        return response()->json([
            'permissions' => $permissions
        ], 200);
    }
}
