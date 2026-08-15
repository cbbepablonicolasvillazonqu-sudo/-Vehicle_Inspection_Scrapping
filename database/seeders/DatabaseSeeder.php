<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra la base de datos de Forte Towing.
     *
     * En producción NO se usa este seeder: allí va ProduccionSeeder, que solo
     * crea roles y permisos, más el comando forte:crear-admin.
     */
    public function run(): void
    {
        // Roles y permisos: siempre. Es idempotente y no toca cuentas.
        $this->call(RolesYPermisosSeeder::class);

        // Las 4 cuentas de prueba reescriben su contraseña a "password" en cada
        // corrida, así que solo tienen sentido junto a los datos de demostración.
        // Se lee de config y no de env(): con la configuración cacheada, env()
        // devuelve siempre el default y esto se saltaría en silencio.
        if (! config('forte.seed_demo_data')) {
            $this->command?->warn('SEED_DEMO_DATA=false: se sembraron solo roles y permisos.');
            $this->command?->line('Para crear el administrador: php artisan forte:crear-admin <correo> --password=...');

            return;
        }

        $this->call([
            UsuariosSeeder::class,
            DemoVehiculosSeeder::class,
        ]);
    }
}
