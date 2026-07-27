<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorDesguace;
use App\Livewire\Vehiculos\GestorGastos;
use App\Livewire\Vehiculos\GestorVenta;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FlujoVentaTest extends TestCase
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

    public function test_mecanico_registra_gasto_de_reparacion(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();
        $mecanico = $this->usuarioConRol('mecanico');

        Livewire::actingAs($mecanico)
            ->test(GestorGastos::class, ['vehiculo' => $vehiculo])
            ->call('nuevo')
            ->set('categoria', 'reparacion')
            ->set('descripcion', 'Cambio de alternador')
            ->set('monto', '220.50')
            ->set('fecha', now()->format('Y-m-d'))
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'vehicle_id' => $vehiculo->id,
            'categoria' => 'reparacion',
            'monto' => '220.50',
            'user_id' => $mecanico->id,
        ]);

        $this->assertSame(220.50, $vehiculo->fresh()->totalGastos());
        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'gasto_registrado',
        ]);
    }

    public function test_vendedor_registra_venta_y_el_vehiculo_queda_vendido_y_bloqueado(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Publicado)->create(['precio_compra' => 2000]);
        $vehiculo->gastos()->create([
            'categoria' => 'piezas',
            'descripcion' => 'Batería',
            'monto' => 500,
            'fecha' => now()->format('Y-m-d'),
        ]);

        $vendedor = $this->usuarioConRol('vendedor');

        Livewire::actingAs($vendedor)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo])
            ->set('fecha_venta', now()->format('Y-m-d'))
            ->set('precio_venta', '4000')
            ->set('nombre_comprador', 'Juan Pérez')
            ->set('telefono_comprador', '555-1234')
            ->set('metodo_pago', 'efectivo')
            ->call('registrar')
            ->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertSame(EstadoVehiculo::Vendido, $vehiculo->estado);
        $this->assertTrue($vehiculo->estaBloqueado());
        $this->assertSame('Juan Pérez', $vehiculo->venta->nombre_comprador);

        // Ganancia = 4000 − 2000 − 500 = 1500
        $this->assertSame(1500.0, $vehiculo->ganancia());

        // Historial y auditoría registrados
        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'estado_anterior' => 'publicado',
            'estado_nuevo' => 'vendido',
            'user_id' => $vendedor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'venta_registrada']);

        // Bloqueado: el mecánico ya no puede editar; admin sí.
        $otroRol = $this->usuarioConRol('mecanico');
        $admin = $this->usuarioConRol('admin');
        $this->assertFalse($otroRol->can('update', $vehiculo));
        $this->assertTrue($admin->can('update', $vehiculo));

        // El vendedor no puede volver a vender ni cambiar estado.
        $this->assertFalse(
            Livewire::actingAs($vendedor)->test(GestorVenta::class, ['vehiculo' => $vehiculo])
                ->instance()->puedeVender()
        );
    }

    public function test_vendedor_no_puede_vender_vehiculo_en_reparacion(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();
        $vendedor = $this->usuarioConRol('vendedor');

        Livewire::actingAs($vendedor)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo])
            ->set('fecha_venta', now()->format('Y-m-d'))
            ->set('precio_venta', '3000')
            ->set('nombre_comprador', 'X')
            ->set('telefono_comprador', '1')
            ->set('metodo_pago', 'efectivo')
            ->call('registrar')
            ->assertForbidden();

        $this->assertNull($vehiculo->fresh()->venta);
    }

    public function test_admin_edita_venta_cerrada_y_puede_revertirla(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Publicado)->create();
        $vendedor = $this->usuarioConRol('vendedor');
        $admin = $this->usuarioConRol('admin');

        // Vendedor vende.
        Livewire::actingAs($vendedor)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo])
            ->set('fecha_venta', now()->format('Y-m-d'))
            ->set('precio_venta', '3500')
            ->set('nombre_comprador', 'Ana')
            ->set('telefono_comprador', '555-9999')
            ->set('metodo_pago', 'transferencia')
            ->call('registrar');

        // Vendedor NO puede editar la venta cerrada.
        Livewire::actingAs($vendedor)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo->fresh()])
            ->call('editar')
            ->assertForbidden();

        // Admin sí la edita.
        Livewire::actingAs($admin)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo->fresh()])
            ->call('editar')
            ->set('precio_venta', '3800')
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertSame('3800.00', (string) $vehiculo->fresh()->venta->precio_venta);
        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'venta_editada']);

        // Admin elimina la venta → vuelve al estado anterior (publicado).
        Livewire::actingAs($admin)
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo->fresh()])
            ->call('eliminarVenta')
            ->assertHasNoErrors();

        $vehiculo->refresh();
        $this->assertNull($vehiculo->venta);
        $this->assertSame(EstadoVehiculo::Publicado, $vehiculo->estado);
    }

    public function test_admin_registra_desguace_y_calcula_perdida(): void
    {
        $vehiculo = Vehicle::factory()->create(['precio_compra' => 800]); // comprado
        $admin = $this->usuarioConRol('admin');

        Livewire::actingAs($admin)
            ->test(GestorDesguace::class, ['vehiculo' => $vehiculo])
            ->set('fecha', now()->format('Y-m-d'))
            ->set('monto_recibido', '300')
            ->set('empresa', 'Junkyard Central')
            ->call('registrar')
            ->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertSame(EstadoVehiculo::Desguace, $vehiculo->estado);
        $this->assertSame(-500.0, $vehiculo->ganancia()); // 300 − 800
        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'desguace_registrado']);

        // Un rol sin permiso no puede registrar Junk car.
        $otroRol = $this->usuarioConRol('mecanico');
        $otro = Vehicle::factory()->create();

        Livewire::actingAs($otroRol)
            ->test(GestorDesguace::class, ['vehiculo' => $otro])
            ->set('fecha', now()->format('Y-m-d'))
            ->set('monto_recibido', '100')
            ->set('empresa', 'X')
            ->call('registrar')
            ->assertForbidden();
    }

    public function test_gastos_bloqueados_en_vehiculo_vendido_salvo_admin(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();
        $mecanico = $this->usuarioConRol('mecanico');
        $admin = $this->usuarioConRol('admin');

        $this->assertFalse(
            Livewire::actingAs($mecanico)->test(GestorGastos::class, ['vehiculo' => $vehiculo])
                ->instance()->puedeRegistrar()
        );

        $this->assertTrue(
            Livewire::actingAs($admin)->test(GestorGastos::class, ['vehiculo' => $vehiculo])
                ->instance()->puedeRegistrar()
        );
    }
}
