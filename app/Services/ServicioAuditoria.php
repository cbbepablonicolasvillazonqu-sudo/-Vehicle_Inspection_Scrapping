<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;

/**
 * Registra las acciones sobre un vehículo en la bitácora de auditoría.
 */
class ServicioAuditoria
{
    public function registrar(Vehicle $vehiculo, string $accion, array $detalles = [], ?User $usuario = null): AuditLog
    {
        return $vehiculo->auditoria()->create([
            'user_id' => $usuario?->id ?? auth()->id(),
            'accion' => $accion,
            'detalles' => $detalles ?: null,
        ]);
    }

    /**
     * Diff de atributos para auditar ediciones: [campo => [antes, después]].
     */
    public function diferencias(array $original, array $nuevo, array $campos): array
    {
        $diff = [];

        foreach ($campos as $campo) {
            $antes = $original[$campo] ?? null;
            $despues = $nuevo[$campo] ?? null;

            if ((string) $antes !== (string) $despues) {
                $diff[$campo] = ['antes' => $antes, 'despues' => $despues];
            }
        }

        return $diff;
    }
}
