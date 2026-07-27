<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Toda acción auditada debe tener una etiqueta propia: si cae en el comodín
 * se muestra el nombre técnico crudo y además queda sin traducir al inglés.
 */
class AuditLogTest extends TestCase
{
    public static function accionesDelFlujoDeGrua(): array
    {
        return [
            'recojo asignado' => ['recojo_asignado', 'Recojo asignado'],
            'recojo registrado' => ['recojo_registrado', 'Recojo registrado'],
            'enviado a junk' => ['enviado_a_junk', 'Enviado a Junk car'],
            'junk completado' => ['junk_car_completado', 'Junk car completado'],
            'foto del vehiculo' => ['foto_vehiculo_actualizada', 'Foto del vehículo actualizada'],
        ];
    }

    #[DataProvider('accionesDelFlujoDeGrua')]
    public function test_cada_accion_tiene_su_propia_etiqueta(string $accion, string $esperada): void
    {
        $registro = new AuditLog(['accion' => $accion]);

        $this->assertSame($esperada, $registro->etiquetaAccion());
    }

    public function test_una_accion_desconocida_cae_en_el_comodin(): void
    {
        $registro = new AuditLog(['accion' => 'algo_nuevo_sin_etiqueta']);

        $this->assertSame('Algo nuevo sin etiqueta', $registro->etiquetaAccion());
    }
}
