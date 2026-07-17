<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Crea los permisos y los 4 roles del negocio.
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
            'ver precios compra',
            'ver ganancias',
            'gestionar usuarios',
            'exportar datos',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        // Admin: acceso total.
        Role::findOrCreate('admin', 'web')->syncPermissions($permisos);

        // Comprador: registra vehículos, los edita, sube fotos y los pasa a reparación.
        Role::findOrCreate('comprador', 'web')->syncPermissions([
            'ver vehiculos',
            'crear vehiculos',
            'editar vehiculos',
            'cambiar estado',
            'registrar gastos',
            'subir fotos',
            'ver precios compra',
            'exportar datos',
        ]);

        // Mecánico: solo vehículos en reparación; registra reparaciones (gastos) y fotos.
        // No tiene "ver precios compra" ni "ver ganancias".
        Role::findOrCreate('mecanico', 'web')->syncPermissions([
            'ver vehiculos',
            'cambiar estado',
            'registrar gastos',
            'subir fotos',
        ]);

        // Vendedor: vehículos listos/publicados; registra ventas.
        Role::findOrCreate('vendedor', 'web')->syncPermissions([
            'ver vehiculos',
            'cambiar estado',
            'registrar ventas',
            'subir fotos',
            'exportar datos',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
