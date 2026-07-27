<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Crea las 4 cuentas del equipo de Forte Towing.
     *
     * Contraseña inicial de todas: "password"
     * (cambiarlas desde Perfil o desde Usuarios al primer ingreso).
     */
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Administrador', 'email' => 'admin@fortetowing.com', 'rol' => 'admin'],
            ['name' => 'Gruero', 'email' => 'gruero@fortetowing.com', 'rol' => 'gruero'],
            ['name' => 'Mecánico', 'email' => 'taller@fortetowing.com', 'rol' => 'mecanico'],
            ['name' => 'Vendedor', 'email' => 'ventas@fortetowing.com', 'rol' => 'vendedor'],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::updateOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $usuario->syncRoles([$datos['rol']]);
        }
    }
}
