<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Permission\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Spatie\Permission\Models\Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use UUIDs to ensure uniqueness for large generation counts
        return [
            'name' => 'role-'.fake()->uuid(),
            'guard_name' => 'web',
        ];
    }
}
