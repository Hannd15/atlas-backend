<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ModulePermissionBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pg_module_token_can_seed_permissions(): void
    {
        $module = Module::factory()->create(['slug' => 'pg']);
        $plainToken = $this->issueModuleToken($module);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/auth/permissions/batch', [
                'permissions' => [
                    ['name' => 'pg.permissions.manage'],
                ],
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('permissions', ['name' => 'pg.permissions.manage']);
    }

    public function test_user_token_can_seed_permissions_batch(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/auth/permissions/batch', [
                'permissions' => [
                    ['name' => 'user.permissions.seed'],
                ],
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('permissions', ['name' => 'user.permissions.seed']);
    }

    public function test_unlisted_module_is_blocked_from_batch_endpoint(): void
    {
        $module = Module::factory()->create(['slug' => 'other']);
        $plainToken = $this->issueModuleToken($module);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/auth/permissions/batch', [
                'permissions' => [
                    ['name' => 'blocked.permissions.seed'],
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('permissions', ['name' => 'blocked.permissions.seed']);
    }

    private function issueModuleToken(Module $module, string $plainToken = 'module-token'): string
    {
        $token = new PersonalAccessToken;
        $token->forceFill([
            'tokenable_type' => Module::class,
            'tokenable_id' => $module->id,
            'name' => 'Test Module Token',
            'token' => hash('sha256', $plainToken),
            'abilities' => ['permissions:batch'],
        ])->save();

        return $plainToken;
    }
}
