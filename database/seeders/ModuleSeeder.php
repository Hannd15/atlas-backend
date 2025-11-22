<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class ModuleSeeder extends Seeder
{
    private const PG_SLUG = 'pg';

    /**
     * Seed the application's module tokens.
     */
    public function run(): void
    {
        $token = config('services.pg_module.token');

        if (blank($token)) {
            $this->command?->warn('MODULE_PG_TOKEN is not defined. Skipping PG module token seeding.');

            return;
        }

        $module = Module::updateOrCreate(
            ['slug' => self::PG_SLUG],
            [
                'name' => config('services.pg_module.name', Str::upper(self::PG_SLUG)),
                'description' => config('services.pg_module.description', 'Permisos gestionados desde el módulo PG.'),
                'is_active' => true,
            ]
        );

        $hashedToken = hash('sha256', $token);

        $personalToken = PersonalAccessToken::query()
            ->where('tokenable_type', Module::class)
            ->where('tokenable_id', $module->id)
            ->where('name', 'PG Persistent Token')
            ->first();

        if (! $personalToken) {
            $personalToken = new PersonalAccessToken;
            $personalToken->forceFill([
                'tokenable_type' => Module::class,
                'tokenable_id' => $module->id,
                'name' => 'PG Persistent Token',
            ]);
        }

        $personalToken->forceFill([
            'token' => $hashedToken,
            'abilities' => ['permissions:batch'],
            'expires_at' => null,
        ])->save();
    }
}
