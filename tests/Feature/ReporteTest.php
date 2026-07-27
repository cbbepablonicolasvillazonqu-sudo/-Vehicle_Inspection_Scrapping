<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioRentabilidad;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function vehiculoVendido(float $compra, float $gasto, float $venta, string $fechaVenta): Vehicle
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create(['precio_compra' => $compra]);

        if ($gasto > 0) {
            $vehiculo->gastos()->create([
                'categoria' => 'reparacion',
                'descripcion' => 'Trabajos varios',
                'monto' => $gasto,
                'fecha' => $fechaVenta,
            ]);
        }

        $vehiculo->venta()->create([
            'fecha_venta' => $fechaVenta,
            'precio_venta' => $venta,
            'nombre_comprador' => 'Cliente',
            'telefono_comprador' => '555',
            'metodo_pago' => 'efectivo',
        ]);

        return $vehiculo;
    }

    public function test_servicio_de_rentabilidad_calcula_mes_y_acumulado(): void
    {
        // Este mes: 4000 − 2000 − 500 = 1500
        $this->vehiculoVendido(2000, 500, 4000, now()->format('Y-m-d'));

        // Mes pasado: 3000 − 1000 − 0 = 2000
        $this->vehiculoVendido(1000, 0, 3000, now()->subMonthNoOverflow()->format('Y-m-d'));

        // Desguace este mes: 300 − 800 = −500
        $chatarra = Vehicle::factory()->enEstado(EstadoVehiculo::Desguace)->create(['precio_compra' => 800]);
        $chatarra->desguace()->create([
            'fecha' => now()->format('Y-m-d'),
            'monto_recibido' => 300,
            'empresa' => 'Junkyard',
        ]);

        $servicio = app(ServicioRentabilidad::class);

        $this->assertSame(1000.0, $servicio->gananciaDelMes(now()));       // 1500 − 500
        $this->assertSame(3000.0, $servicio->gananciaAcumulada());        // 1500 + 2000 − 500
    }

    public function test_total_invertido_solo_cuenta_inventario_activo(): void
    {
        // Activo: compra 2000 + gastos 300
        $activo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create(['precio_compra' => 2000]);
        $activo->gastos()->create([
            'categoria' => 'piezas', 'descripcion' => 'Piezas', 'monto' => 300, 'fecha' => now()->format('Y-m-d'),
        ]);

        // Vendido: no debe contar.
        $this->vehiculoVendido(9999, 0, 12000, now()->format('Y-m-d'));

        $this->assertSame(2300.0, app(ServicioRentabilidad::class)->totalInvertidoInventario());
    }

    public function test_panel_muestra_finanzas_solo_al_admin(): void
    {
        $this->vehiculoVendido(2000, 500, 4000, now()->format('Y-m-d'));

        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/panel')
            ->assertOk()
            ->assertSee('Total invertido')
            ->assertSee('Ganancia');

        $this->actingAs($this->usuarioConRol('vendedor'))
            ->get('/panel')
            ->assertOk()
            ->assertDontSee('Total invertido')
            ->assertDontSee('Ganancia acumulada');
    }

    public function test_reporte_y_export_de_ganancias_son_solo_admin(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))->get('/reportes/ganancias')->assertOk();

        foreach (['gruero', 'mecanico', 'vendedor'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))->get('/reportes/ganancias')->assertForbidden();
            $this->actingAs(User::factory()->create()->assignRole($rol))->get('/exportar/ganancias')->assertForbidden();
        }
    }

    public function test_export_de_vehiculos_respeta_permiso(): void
    {
        Vehicle::factory()->create();

        // Vendedor tiene "exportar datos".
        $this->actingAs($this->usuarioConRol('vendedor'))
            ->get('/exportar/vehiculos/csv')
            ->assertOk();

        // Mecánico no lo tiene.
        $this->actingAs($this->usuarioConRol('mecanico'))
            ->get('/exportar/vehiculos/csv')
            ->assertForbidden();
    }
}
