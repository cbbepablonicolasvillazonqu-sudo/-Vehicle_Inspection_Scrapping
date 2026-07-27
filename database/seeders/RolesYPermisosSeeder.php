<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Crea los permisos y los 4 roles del negocio:
     * admin, gruero, mecanico y vendedor.
     *
     * Es idempotente: puede ejecutarse varias veces sin duplicar datos.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Abrir la ficha de un vehículo (alcance según el rol).
            'ver vehiculos',
            // Entrar al listado del inventario. El Gruero no lo tiene: él
            // trabaja solo desde su panel de recojos y Junk car.
            'ver inventario',
            'crear vehiculos',
            'editar vehiculos',
            'eliminar vehiculos',
            'cambiar estado',
            'registrar gastos',
            'subir fotos',
            'registrar ventas',
            'editar ventas cerradas',
            'registrar desguace',
            'fijar precio venta',
            'ver precios compra',
            'ver ganancias',
            'gestionar usuarios',
            'exportar datos',
            // Flujo de grúa: el Admin asigna el recojo y el Gruero lo registra.
            'asignar recojo',
            'registrar recojo',
            // Envío a Junk car: lo decide únicamente el Admin.
            'enviar a junk',
            // Completar los datos del Junk car (catalizador y monto pagado).
            'completar junk',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        // Admin: acceso total.
        Role::findOrCreate('admin', 'web')->syncPermissions($permisos);

        // Gruero: trabaja solo desde su panel (recojos y Junk car). No entra
        // al inventario: abre la ficha únicamente de los vehículos que el Admin
        // le asignó, para registrar el recojo y completar el Junk car.
        // No decide qué vehículo va a Junk car: eso es exclusivo del Admin.
        Role::findOrCreate('gruero', 'web')->syncPermissions([
            'ver vehiculos',
            'registrar recojo',
            'completar junk',
        ]);

        // Mecánico: vehículos comprados/en reparación/listos; registra gastos
        // (con fotos). No ve precios de compra ni ganancias.
        Role::findOrCreate('mecanico', 'web')->syncPermissions([
            'ver vehiculos',
            'ver inventario',
            'cambiar estado',
            'registrar gastos',
            'subir fotos',
        ]);

        // Vendedor: vehículos listos/publicados; registra ventas. Sin fotos.
        Role::findOrCreate('vendedor', 'web')->syncPermissions([
            'ver vehiculos',
            'ver inventario',
            'cambiar estado',
            'registrar ventas',
            'exportar datos',
        ]);

        // El rol "comprador" fue reemplazado por "gruero": se retira si existe.
        Role::where('name', 'comprador')->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
