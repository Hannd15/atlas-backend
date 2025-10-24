<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Permission\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Spatie\Permission\Models\Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Deterministic numeric naming pattern to avoid collisions and
        // provide predictable values for seeds and tests. Produces
        // permission-1, permission-2, ... per PHP process execution.
        static $counter = 1;

        return [
            'name' => 'permission-'.$counter++,
            'guard_name' => 'web',
        ];
    }
}
