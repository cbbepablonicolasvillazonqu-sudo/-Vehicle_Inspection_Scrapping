<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->can('ver vehiculos');
    }

    public function view(User $usuario, Vehicle $vehiculo): bool
    {
        return $usuario->can('ver vehiculos') && $vehiculo->esVisiblePara($usuario);
    }

    public function create(User $usuario): bool
    {
        return $usuario->can('crear vehiculos');
    }

    public function update(User $usuario, Vehicle $vehiculo): bool
    {
        // Vendido/Desguace: solo Admin puede tocar el registro.
        if ($usuario->hasRole('admin')) {
            return true;
        }

        return $usuario->can('editar vehiculos') && ! $vehiculo->estaBloqueado();
    }

    public function delete(User $usuario, Vehicle $vehiculo): bool
    {
        // Hoy solo el Admin tiene este permiso: el comportamiento es el mismo
        // que antes, pero la regla vive en un único lugar (el seeder de roles).
        return $usuario->can('eliminar vehiculos');
    }
}
