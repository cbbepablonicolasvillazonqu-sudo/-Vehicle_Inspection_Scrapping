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
            'ver vehiculos',
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
            // Envío masivo a Junk car (Admin y Gruero).
            'enviar a junk',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        // Admin: acceso total.
        Role::findOrCreate('admin', 'web')->syncPermissions($permisos);

        // Gruero: solo ve los vehículos que el Admin le asigna. Registra el
        // recojo (pago, destino, catalizador, monto) y puede enviar a Junk car.
        Role::findOrCreate('gruero', 'web')->syncPermissions([
            'ver vehiculos',
            'registrar recojo',
            'enviar a junk',
        ]);

        // Mecánico: vehículos comprados/en reparación/listos; registra gastos
        // (con fotos). No ve precios de compra ni ganancias.
        Role::findOrCreate('mecanico', 'web')->syncPermissions([
            'ver vehiculos',
            'cambiar estado',
            'registrar gastos',
            'subir fotos',
        ]);

        // Vendedor: vehículos listos/publicados; registra ventas. Sin fotos.
        Role::findOrCreate('vendedor', 'web')->syncPermissions([
            'ver vehiculos',
            'cambiar estado',
            'registrar ventas',
            'exportar datos',
        ]);

        // El rol "comprador" fue reemplazado por "gruero": se retira si existe.
        Role::where('name', 'comprador')->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
