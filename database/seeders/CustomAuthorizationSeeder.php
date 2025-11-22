<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomAuthorizationSeeder extends Seeder
{
    /**
     * Permissions that should always exist in the system.
     * Modify these values when you need new, deterministic permissions.
     *
     * @var string[]
     */
    protected array $permissions = [
        'ver permisos',
        'crear permisos',
        'editar permisos',
        'eliminar permisos',
        'asignar permisos',
        'ver roles',
        'crear roles',
        'editar roles',
        'eliminar roles',
        'asignar roles',
        'ver usuarios',
        'crear usuarios',
        'editar usuarios',
        'eliminar usuarios',
    ];

    /**
     * Roles and their explicit permissions. The keys should match the permission names above.
     * Update this map whenever you need to change a role's abilities.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $roles = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Populate roles at runtime so we can reference $this->permissions (not allowed in property defaults)
        if (empty($this->roles)) {
            $this->roles = [
                [
                    'name' => 'Super Administrador',
                    'guard_name' => 'web',
                    'permissions' => $this->permissions,
                ],
            ];
        }

        $permissionRegistry = [];

        foreach ($this->permissions as $permissionName) {
            $permissionRegistry[$permissionName] = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach ($this->roles as $definition) {
            $role = Role::firstOrCreate([
                'name' => $definition['name'],
                'guard_name' => $definition['guard_name'] ?? 'web',
            ]);

            if (! empty($definition['permissions'])) {
                $permissions = array_map(fn (string $permissionName) => $permissionRegistry[$permissionName], $definition['permissions']);
                $role->syncPermissions($permissions);
            }
        }
    }
}