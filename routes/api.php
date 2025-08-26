<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

// UserController CRUD and custom routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::get('/users/{id}/roles', [UserController::class, 'getRoles']);
Route::get('/users/{id}/permissions', [UserController::class, 'getPermissions']);
Route::get('/users/{id}/info', [UserController::class, 'getInfo']);
Route::post('/users/{userId}/permissions/{permissionId}', [UserController::class, 'assignPermission']);
Route::delete('/users/{userId}/permissions/{permissionId}', [UserController::class, 'revokePermission']);

// RoleController CRUD and custom routes
Route::get('/roles', [RoleController::class, 'index']);
Route::post('/roles', [RoleController::class, 'create']);
Route::put('/roles/{id}', [RoleController::class, 'update']);
Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
Route::get('/roles/{id}/permissions', [RoleController::class, 'getPermissions']);
Route::post('/roles/{roleId}/permissions/{permissionId}', [RoleController::class, 'assignPermission']);
Route::delete('/roles/{roleId}/permissions/{permissionId}', [RoleController::class, 'revokePermission']);

// PermissionController routes
Route::get('/permissions', [App\Http\Controllers\PermissionController::class, 'index']);

