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

    public function test_admin_registra_vehiculo_con_historial_y_auditoria(): void
    {
        $admin = $this->usuarioConRol('admin');

        Livewire::actingAs($admin)
            ->test(FormularioVehiculo::class)
            ->set('marca', 'Toyota')
            ->set('modelo', 'Corolla')
            ->set('anio', '2015')
            ->set('vin', '1hgbh41jxmn109186')
            ->set('millas', '120000')
            ->set('precio_compra', '2500')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('ubicacion_destino', 'oficina_1_aldi')
            ->set('estado_titulo', 'clean')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect();

        $vehiculo = Vehicle::firstOrFail();

        $this->assertSame('1HGBH41JXMN109186', $vehiculo->vin); // VIN normalizado a mayúsculas
        $this->assertSame(EstadoVehiculo::Comprado, $vehiculo->estado);
        $this->assertSame($admin->id, $vehiculo->created_by);

        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'estado_anterior' => null,
            'estado_nuevo' => 'comprado',
            'user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'vehiculo_creado',
        ]);
    }

    public function test_vin_invalido_es_rechazado(): void
    {
        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(FormularioVehiculo::class)
            ->set('marca', 'Ford')
            ->set('modelo', 'Focus')
            ->set('anio', '2012')
            ->set('vin', 'CORTO123')
            ->set('millas', '90000')
            ->set('precio_compra', '1800')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('ubicacion_destino', 'casa_hugo')
            ->set('estado_titulo', 'rebuild')
            ->call('guardar')
            ->assertHasErrors(['vin']);
    }

    public function test_vendedor_y_mecanico_no_pueden_abrir_el_formulario(): void
    {
        $this->actingAs($this->usuarioConRol('vendedor'))->get('/vehiculos/crear')->assertForbidden();
        $this->actingAs($this->usuarioConRol('mecanico'))->get('/vehiculos/crear')->assertForbidden();
    }

    public function test_mecanico_ve_pendientes_en_reparacion_y_listos(): void
    {
        $comprado = Vehicle::factory()->enEstado(EstadoVehiculo::Comprado)->create(['marca' => 'Nissan', 'modelo' => 'Sentra']);
        $enReparacion = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create(['marca' => 'Honda', 'modelo' => 'Civic']);
        $listo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create(['marca' => 'Toyota', 'modelo' => 'Camry']);
        $publicado = Vehicle::factory()->enEstado(EstadoVehiculo::Publicado)->create(['marca' => 'Ford', 'modelo' => 'F-150']);

        $mecanico = $this->usuarioConRol('mecanico');

        // Ve los pendientes de revisión, los del taller y los listos…
        Livewire::actingAs($mecanico)
            ->test(ListaVehiculos::class)
            ->assertSee('Sentra')
            ->assertSee('Civic')
            ->assertSee('Camry')
            ->assertDontSee('F-150'); // …pero no los publicados/vendidos.

        $this->actingAs($mecanico)->get("/vehiculos/{$comprado->id}")->assertOk();
        $this->actingAs($mecanico)->get("/vehiculos/{$listo->id}")->assertOk();
        $this->actingAs($mecanico)->get("/vehiculos/{$publicado->id}")->assertForbidden();
    }

    public function test_mecanico_inicia_la_revision_y_puede_revertir_un_listo(): void
    {
        $servicio = app(ServicioEstadoVehiculo::class);
        $mecanico = $this->usuarioConRol('mecanico');
        $vehiculo = Vehicle::factory()->create(); // comprado / pendiente de revisión

        // Puede iniciar la revisión él mismo (Comprado → En reparación).
        $servicio->cambiar($mecanico, $vehiculo, EstadoVehiculo::EnReparacion, 'ingresa al taller');
        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);

        // Termina el trabajo y lo marca listo.
        $servicio->cambiar($mecanico, $vehiculo->fresh(), EstadoVehiculo::Listo);

        // La ficha del "Listo" sigue siendo visible para él (antes daba 403,
        // dejando muerto el retroceso Listo → En reparación de la matriz)…
        $this->actingAs($mecanico)->get("/vehiculos/{$vehiculo->id}")->assertOk();

        // …así que puede revertirlo si detecta un problema.
        $servicio->cambiar($mecanico, $vehiculo->fresh(), EstadoVehiculo::EnReparacion, 'se detectó una fuga');
        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);

        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'estado_anterior' => 'comprado',
            'estado_nuevo' => 'en_reparacion',
            'user_id' => $mecanico->id,
            'nota' => 'ingresa al taller',
        ]);
    }

    public function test_transiciones_de_estado_por_rol(): void
    {
        $servicio = app(ServicioEstadoVehiculo::class);
        $vehiculo = Vehicle::factory()->create(); // comprado

        $gruero = $this->usuarioConRol('gruero');
        $mecanico = $this->usuarioConRol('mecanico');
        $vendedor = $this->usuarioConRol('vendedor');

        // Mecánico: comprado → en reparación (él inicia la revisión)
        $servicio->cambiar($mecanico, $vehiculo, EstadoVehiculo::EnReparacion);
        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);

        // El gruero no participa del flujo de estados
        try {
            $servicio->cambiar($gruero, $vehiculo->fresh(), EstadoVehiculo::Listo);
            $this->fail('El gruero no debería poder cambiar estados.');
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
