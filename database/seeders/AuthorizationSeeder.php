<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AuthorizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 500 permissions
        $permissions = \Database\Factories\PermissionFactory::new()->count(500)->create();

        // Create 100 roles
        $roles = \Database\Factories\RoleFactory::new()->count(100)->create();

        // Assign random permissions to each role
        foreach ($roles as $role) {
            $randomPermissions = $permissions->random(rand(10, 50));
            $role->syncPermissions($randomPermissions);
        }

        // Create 10 users (smaller number for local/dev)
        $users = \App\Models\User::factory(10)->create();

        // Assign random roles (1-5) to each user
        foreach ($users as $user) {
            $randomRoles = $roles->random(rand(1, 5));
            $user->syncRoles($randomRoles);
        }
    }
}
