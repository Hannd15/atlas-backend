<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Permission;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition()
    {
        static $increment = 1;
        return [
            'name' => $this->faker->word() . '_' . $increment++, // ensures uniqueness
            'guard_name' => 'web',
        ];
    }
}
