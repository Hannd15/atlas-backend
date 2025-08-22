<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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

    // Create 500 roles
    $roles = \Database\Factories\RoleFactory::new()->count(500)->create();

        // Assign random permissions to each role
        foreach ($roles as $role) {
            $randomPermissions = $permissions->random(rand(10, 50));
            $role->syncPermissions($randomPermissions);
        }

        // Create 500 users
        $users = \App\Models\User::factory(500)->create();

        // Assign random roles to each user
        foreach ($users as $user) {
            $randomRoles = $roles->random(rand(1, 10));
            $user->syncRoles($randomRoles);
        }
    }
}
