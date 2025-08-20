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
        $roles = [
            'admin'=> [
                'admin-permission-1',
                'admin-permission-2',
            ],
            'profesor'=> [
                'profesor-permission-1',
                'profesor-permission-2',
            ],
            'estudiante'=> [
                'estudiante-permission-1',
                'estudiante-permission-2',
            ]
        ];

        foreach ($roles as $role => $permissions) {
            $roleModel = Role::create(['name' => $role]);
            foreach ($permissions as $permission) {
                $permission = Permission::create(['name' => $permission]);
                $roleModel->givePermissionTo($permission);
            }
        }
    }
}
