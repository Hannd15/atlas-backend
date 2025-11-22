<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UsersByPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_users_with_the_given_permission(): void
    {
        $permission = Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);

        $authorizedUser = User::factory()->create(['name' => 'Authorized User']);
        $authorizedUser->givePermissionTo($permission);

        $otherPermission = Permission::create(['name' => 'other.permission', 'guard_name' => 'web']);
        $otherUser = User::factory()->create(['name' => 'Other User']);
        $otherUser->givePermissionTo($otherPermission);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/auth/users/by-permission/'.$permission->name);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $authorizedUser->id,
                'name' => 'Authorized User',
            ]);
    }

    public function test_it_returns_404_when_permission_does_not_exist(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/auth/users/by-permission/non-existent-permission');

        $response->assertNotFound()
            ->assertJson(['error' => 'Permiso no encontrado.']);
    }
}
