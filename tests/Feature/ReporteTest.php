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

    /* ---- Salidas pendientes de valorar (Junk car masivo sin completar) ---- */

    /** Vehículo enviado a Junk car sin monto: el flujo del envío masivo. */
    private function junkSinMonto(float $compra): Vehicle
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Desguace)->create(['precio_compra' => $compra]);

        $vehiculo->desguace()->create(['fecha' => now()->format('Y-m-d')]);

        return $vehiculo;
    }

    public function test_junk_sin_monto_no_afecta_la_ganancia(): void
    {
        // Venta real: 4000 − 2000 − 500 = 1500
        $this->vehiculoVendido(2000, 500, 4000, now()->format('Y-m-d'));

        $servicio = app(ServicioRentabilidad::class);
        $antes = $servicio->gananciaDelMes(now());

        // Un Junk car sin completar no puede mover el número.
        $this->junkSinMonto(900);

        $this->assertSame($antes, $servicio->gananciaDelMes(now()));
        $this->assertSame(1500.0, $servicio->gananciaDelMes(now()));
        $this->assertSame(1500.0, $servicio->gananciaAcumulada());
    }

    public function test_junk_sin_monto_se_cuenta_como_pendiente(): void
    {
        $vehiculo = $this->junkSinMonto(900);

        $servicio = app(ServicioRentabilidad::class);
        $pendientes = $servicio->salidasPendientes();

        $this->assertCount(1, $pendientes);
        $this->assertSame('sin_monto_junk', $pendientes->first()['motivo']);
        $this->assertNull($pendientes->first()['ganancia']);
        $this->assertNull($pendientes->first()['recuperado']);
        $this->assertSame($vehiculo->id, $pendientes->first()['vehiculo']->id);
        $this->assertSame(1, $servicio->contarPendientesDeValorar());
        $this->assertCount(0, $servicio->salidasValoradas());
    }

    public function test_al_completar_el_junk_la_salida_entra_en_la_ganancia(): void
    {
        $vehiculo = $this->junkSinMonto(800);
        $servicio = app(ServicioRentabilidad::class);

        $this->assertSame(0.0, $servicio->gananciaDelMes(now()));

        // El gruero (o el admin) carga el monto: 300 − 800 = −500
        $vehiculo->desguace->update(['monto_recibido' => 300]);

        $this->assertSame(-500.0, $servicio->gananciaDelMes(now()));
        $this->assertSame(0, $servicio->contarPendientesDeValorar());
    }

    public function test_salida_sin_precio_de_compra_queda_pendiente(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create(['precio_compra' => null]);
        $vehiculo->venta()->create([
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_venta' => 5000,
            'nombre_comprador' => 'Cliente',
            'telefono_comprador' => '555',
            'metodo_pago' => 'efectivo',
        ]);

        $servicio = app(ServicioRentabilidad::class);

        $this->assertSame('sin_precio_compra', $servicio->salidasPendientes()->first()['motivo']);
        $this->assertSame(0.0, $servicio->gananciaAcumulada());
    }

    public function test_la_ganancia_del_modelo_es_null_si_falta_el_monto_del_junk(): void
    {
        $vehiculo = $this->junkSinMonto(800);

        $this->assertNull($vehiculo->montoRecuperado());
        $this->assertNull($vehiculo->ganancia());

        $vehiculo->desguace->update(['monto_recibido' => 300]);
        $vehiculo->refresh()->load('desguace');

        $this->assertSame(300.0, $vehiculo->montoRecuperado());
        $this->assertSame(-500.0, $vehiculo->ganancia());
    }

    public function test_el_panel_avisa_de_las_salidas_pendientes(): void
    {
        $this->junkSinMonto(900);

        \Livewire\Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(\App\Livewire\Dashboard::class)
            ->assertViewHas('finanzas', fn ($f) => $f['pendientes'] === 1);
    }

    public function test_el_reporte_separa_las_pendientes_de_las_valoradas(): void
    {
        $this->vehiculoVendido(2000, 500, 4000, now()->format('Y-m-d'));
        $this->junkSinMonto(900);

        \Livewire\Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(\App\Livewire\Admin\ReporteGanancias::class)
            ->assertViewHas('salidas', fn ($s) => $s->count() === 1)
            ->assertViewHas('pendientes', fn ($p) => $p->count() === 1)
            ->assertViewHas('totalMes', 1500.0)
            ->assertSee('Falta el monto del Junk car');
    }
}
