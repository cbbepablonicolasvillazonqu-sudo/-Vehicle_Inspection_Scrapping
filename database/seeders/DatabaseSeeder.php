<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra la base de datos de Forte Towing.
     */
    public function run(): void
    {
        $this->call([
            RolesYPermisosSeeder::class,
            UsuariosSeeder::class,
        ]);

        // Vehículos de demostración solo si SEED_DEMO_DATA=true (entorno local).
        if (env('SEED_DEMO_DATA', false) && class_exists(DemoVehiculosSeeder::class)) {
            $this->call(DemoVehiculosSeeder::class);
        }
    }
}
