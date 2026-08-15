<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Siembra mínima de producción: solo roles y permisos.
 *
 * NO crea usuarios a propósito. El administrador se crea una única vez con
 *   php artisan forte:crear-admin
 * y el resto del equipo, desde la pantalla de Usuarios.
 *
 * Es idempotente: se puede volver a ejecutar después de cada despliegue para
 * incorporar permisos nuevos sin tocar ninguna cuenta ni contraseña.
 */
class ProduccionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesYPermisosSeeder::class);

        $this->command?->info('Roles y permisos actualizados. Ningún usuario fue modificado.');
    }
}
