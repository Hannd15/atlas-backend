<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TokenVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_verification_requires_all_roles_and_permissions(): void
    {
        $user = User::factory()->create();

        $roleEditor = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $roleManager = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $permissionPublish = Permission::create(['name' => 'publish-articles', 'guard_name' => 'web']);

        $user->syncRoles([$roleEditor, $roleManager]);
        $user->syncPermissions([$permissionPublish]);

        /** @var NewAccessToken $token */
        $token = $user->createToken('integration-test', ['*']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson('/api/auth/token/verify', [
            'roles' => [$roleEditor->name, $roleManager->name],
            'permissions' => [$permissionPublish->name],
        ]);

        $response->assertOk()
            ->assertJson([
                'authorized' => true,
            ]);
    }

    public function test_token_verification_rejects_when_missing_roles_or_permissions(): void
    {
        $user = User::factory()->create();

        $roleEditor = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $permissionPublish = Permission::create(['name' => 'publish-articles', 'guard_name' => 'web']);

        $user->syncRoles([$roleEditor]);
        $user->syncPermissions([$permissionPublish]);

        /** @var NewAccessToken $token */
        $token = $user->createToken('integration-test', ['*']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson('/api/auth/token/verify', [
            'roles' => [$roleEditor->name, 'manager'],
            'permissions' => [$permissionPublish->name, 'delete-articles'],
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'authorized' => false,
                'missing_roles' => ['manager'],
                'missing_permissions' => ['delete-articles'],
            ]);
    }
}
