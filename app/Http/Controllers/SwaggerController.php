<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="ATLAS Backend API",
 *     version="1.0.0",
 *     description="API documentation for ATLAS Backend endpoints."
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="sanctum",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Bearer",
 *   description="Authentication using Laravel Sanctum bearer token",
 * )
 *
 * @OA\Components(
 *   @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
 *   ),
 *   @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin")
 *   ),
 *   @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users.create")
 *   )
 * )
 */
class SwaggerController extends Controller
{
    // This controller is only for Swagger annotations.
}
