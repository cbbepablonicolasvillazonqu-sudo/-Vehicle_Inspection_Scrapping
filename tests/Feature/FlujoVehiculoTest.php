<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\FormularioVehiculo;
use App\Livewire\Vehiculos\ListaVehiculos;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioEstadoVehiculo;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FlujoVehiculoTest extends TestCase
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

    public function test_comprador_registra_vehiculo_con_historial_y_auditoria(): void
    {
        $comprador = $this->usuarioConRol('comprador');

        Livewire::actingAs($comprador)
            ->test(FormularioVehiculo::class)
            ->set('marca', 'Toyota')
            ->set('modelo', 'Corolla')
            ->set('anio', '2015')
            ->set('vin', '1hgbh41jxmn109186')
            ->set('millas', '120000')
            ->set('precio_compra', '2500')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('lugar_compra', 'subasta')
            ->set('estado_titulo', 'en_mano')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect();

        $vehiculo = Vehicle::firstOrFail();

        $this->assertSame('1HGBH41JXMN109186', $vehiculo->vin); // VIN normalizado a mayúsculas
        $this->assertSame(EstadoVehiculo::Comprado, $vehiculo->estado);
        $this->assertSame($comprador->id, $vehiculo->created_by);

        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'estado_anterior' => null,
            'estado_nuevo' => 'comprado',
            'user_id' => $comprador->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'vehiculo_creado',
        ]);
    }

    public function test_vin_invalido_es_rechazado(): void
    {
        Livewire::actingAs($this->usuarioConRol('comprador'))
            ->test(FormularioVehiculo::class)
            ->set('marca', 'Ford')
            ->set('modelo', 'Focus')
            ->set('anio', '2012')
            ->set('vin', 'CORTO123')
            ->set('millas', '90000')
            ->set('precio_compra', '1800')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('lugar_compra', 'particular')
            ->set('estado_titulo', 'pendiente')
            ->call('guardar')
            ->assertHasErrors(['vin']);
    }

    public function test_vendedor_y_mecanico_no_pueden_abrir_el_formulario(): void
    {
        $this->actingAs($this->usuarioConRol('vendedor'))->get('/vehiculos/crear')->assertForbidden();
        $this->actingAs($this->usuarioConRol('mecanico'))->get('/vehiculos/crear')->assertForbidden();
    }

    public function test_mecanico_solo_ve_vehiculos_en_reparacion(): void
    {
        $enReparacion = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create(['marca' => 'Honda', 'modelo' => 'Civic']);
        $comprado = Vehicle::factory()->enEstado(EstadoVehiculo::Comprado)->create(['marca' => 'Nissan', 'modelo' => 'Sentra']);

        $mecanico = $this->usuarioConRol('mecanico');

        Livewire::actingAs($mecanico)
            ->test(ListaVehiculos::class)
            ->assertSee('Civic')
            ->assertDontSee('Sentra');

        // Y tampoco puede abrir la ficha de uno que no está en reparación.
        $this->actingAs($mecanico)->get("/vehiculos/{$comprado->id}")->assertForbidden();
        $this->actingAs($mecanico)->get("/vehiculos/{$enReparacion->id}")->assertOk();
    }

    public function test_transiciones_de_estado_por_rol(): void
    {
        $servicio = app(ServicioEstadoVehiculo::class);
        $vehiculo = Vehicle::factory()->create(); // comprado

        $comprador = $this->usuarioConRol('comprador');
        $mecanico = $this->usuarioConRol('mecanico');
        $vendedor = $this->usuarioConRol('vendedor');

        // Comprador: comprado → en reparación (permitido)
        $servicio->cambiar($comprador, $vehiculo, EstadoVehiculo::EnReparacion);
        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);

        // Comprador NO puede saltar a listo
        try {
            $servicio->cambiar($comprador, $vehiculo->fresh(), EstadoVehiculo::Listo);
            $this->fail('El comprador no debería poder marcar Listo.');
        } catch (ValidationException) {
            // esperado
        }

        // Mecánico: en reparación → listo
        $servicio->cambiar($mecanico, $vehiculo->fresh(), EstadoVehiculo::Listo);
        $this->assertSame(EstadoVehiculo::Listo, $vehiculo->fresh()->estado);

        // Retroceso permitido: mecánico regresa a reparación y vuelve a listo
        $servicio->cambiar($mecanico, $vehiculo->fresh(), EstadoVehiculo::EnReparacion, 'faltó un detalle');
        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);
        $servicio->cambiar($mecanico, $vehiculo->fresh(), EstadoVehiculo::Listo);

        // Vendedor: listo → publicado
        $servicio->cambiar($vendedor, $vehiculo->fresh(), EstadoVehiculo::Publicado);
        $this->assertSame(EstadoVehiculo::Publicado, $vehiculo->fresh()->estado);

        // Cada cambio quedó en el historial con su usuario
        $this->assertSame(5, $vehiculo->historialEstados()->count());
        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'estado_anterior' => 'listo',
            'estado_nuevo' => 'en_reparacion',
            'user_id' => $mecanico->id,
            'nota' => 'faltó un detalle',
        ]);
    }
}
