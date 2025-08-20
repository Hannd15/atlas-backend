<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    
    public function getUserRoles(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        $roles = $user->roles()->get();

        return response()->json([
            'roles' => $roles
        ], 200);
    }


    public function getUserPermissions(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        $permissions = $user->getAllPermissions();

        return response()->json([
            'permissions' => $permissions
        ], 200);
    }

    public function getUserInfo(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        return response()->json([
            'user' => $user
        ], 200);
    }
}
