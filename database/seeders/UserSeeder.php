<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();

        User::factory()
            ->count(10)
            ->create()
            ->each(function (User $user) use ($roles): void {
                if ($roles->isEmpty()) {
                    return;
                }

                $count = max(1, min($roles->count(), rand(1, 3)));
                $user->syncRoles($roles->random($count));
            });
    }
}
