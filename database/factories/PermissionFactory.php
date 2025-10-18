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
        // Use a UUID to guarantee uniqueness even for large seed counts
        return [
            'name' => 'permission-'.fake()->uuid(),
            'guard_name' => 'web',
        ];
    }
}
