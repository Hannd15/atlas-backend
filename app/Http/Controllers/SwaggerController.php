<?php

namespace App\Http\Controllers;

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
 *
 *   @OA\Schema(
 *     schema="User",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Jane Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
 *     @OA\Property(property="roles_list", type="string", example="admin, editor"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 *   ),
 *
 *   @OA\Schema(
 *     schema="Role",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="admin"),
 *     @OA\Property(property="permissions_list", type="string", example="users.create, users.edit"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 *   ),
 *
 *   @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="users.create"),
 *     @OA\Property(property="roles_list", type="string", example="admin, editor"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 *   ),
 *
 *   @OA\Schema(
 *     schema="Dropdown",
 *     type="object",
 *
 *     @OA\Property(property="value", type="integer", example=1),
 *     @OA\Property(property="label", type="string", example="Jane Doe")
 *   )
 * )
 */
class SwaggerController extends Controller
{
    // This controller is only for Swagger annotations.
}
