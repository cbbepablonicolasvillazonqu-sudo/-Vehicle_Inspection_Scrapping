<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\AsignarRecojo;
use App\Livewire\Vehiculos\EnvioJunkCar;
use App\Livewire\Vehiculos\GestorJunkCar;
use App\Livewire\Vehiculos\GestorRecojo;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Flujo del Gruero: el Admin le asigna el recojo y él registra pago, destino
 * y monto. Solo ve lo que tiene asignado; no decide qué va a Junk car, pero
 * sí completa allí el catalizador y el monto pagado.
 */
class GrueroTest extends TestCase
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

    public function test_admin_asigna_un_recojo_a_un_gruero(): void
    {
        $admin = $this->usuarioConRol('admin');
        $gruero = $this->usuarioConRol('gruero');

        Livewire::actingAs($admin)
            ->test(AsignarRecojo::class)
            ->set('marca', 'Toyota')
            ->set('modelo', 'Corolla')
            ->set('anio', '2014')
            ->set('vin', '1hgbh41jxmn109186')
            ->set('ubicacion_origen_url', 'https://maps.app.goo.gl/abc123')
            ->set('asignado_a', $gruero->id)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect();

        $vehiculo = Vehicle::firstOrFail();

        $this->assertSame('1HGBH41JXMN109186', $vehiculo->vin);
        $this->assertSame($gruero->id, $vehiculo->asignado_a);
        $this->assertSame(EstadoVehiculo::Comprado, $vehiculo->estado);
        // Los datos de compra se completan más tarde.
        $this->assertNull($vehiculo->precio_compra);
        $this->assertNull($vehiculo->fecha_compra);

        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'recojo_asignado',
        ]);
    }

    public function test_url_que_no_es_de_google_maps_es_rechazada(): void
    {
        $gruero = $this->usuarioConRol('gruero');

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(AsignarRecojo::class)
            ->set('marca', 'Ford')
            ->set('modelo', 'Focus')
            ->set('anio', '2013')
            ->set('vin', '1HGBH41JXMN109187')
            ->set('ubicacion_origen_url', 'https://sitio-malicioso.com/x')
            ->set('asignado_a', $gruero->id)
            ->call('guardar')
            ->assertHasErrors(['ubicacion_origen_url']);
    }

    public function test_gruero_no_puede_asignar_recojos(): void
    {
        $this->actingAs($this->usuarioConRol('gruero'))
            ->get('/recojos/asignar')
            ->assertForbidden();
    }

    public function test_gruero_solo_ve_los_vehiculos_asignados_a_el(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $otroGruero = $this->usuarioConRol('gruero');

        $suyo = Vehicle::factory()->create(['asignado_a' => $gruero->id, 'marca' => 'Nissan']);
        $ajeno = Vehicle::factory()->create(['asignado_a' => $otroGruero->id, 'marca' => 'Kia']);
        $sinAsignar = Vehicle::factory()->create(['marca' => 'Mazda']);

        $this->assertTrue($suyo->esVisiblePara($gruero));
        $this->assertFalse($ajeno->esVisiblePara($gruero));
        $this->assertFalse($sinAsignar->esVisiblePara($gruero));

        $this->actingAs($gruero)->get("/vehiculos/{$suyo->id}")->assertOk();
        $this->actingAs($gruero)->get("/vehiculos/{$ajeno->id}")->assertForbidden();
    }

    public function test_gruero_registra_pago_destino_y_monto(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $vehiculo = Vehicle::factory()->create(['asignado_a' => $gruero->id]);

        Livewire::actingAs($gruero)
            ->test(GestorRecojo::class, ['vehiculo' => $vehiculo])
            ->set('metodo_pago_gruero', 'zelle')
            ->set('ubicacion_destino', 'casa_hugo')
            ->set('monto_pagado', '450')
            ->call('guardar')
            ->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertSame('zelle', $vehiculo->metodo_pago_gruero->value);
        $this->assertSame('casa_hugo', $vehiculo->ubicacion_destino->value);
        $this->assertSame('CASA HUGO', $vehiculo->ubicacion_destino->etiqueta());
        $this->assertSame('450.00', (string) $vehiculo->monto_pagado);

        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'recojo_registrado',
        ]);
    }

    public function test_gruero_no_puede_registrar_recojo_de_un_vehiculo_ajeno(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $ajeno = Vehicle::factory()->create(['asignado_a' => $this->usuarioConRol('gruero')->id]);

        Livewire::actingAs($gruero)
            ->test(GestorRecojo::class, ['vehiculo' => $ajeno])
            ->set('metodo_pago_gruero', 'efectivo')
            ->set('ubicacion_destino', 'oficina_1_aldi')
            ->set('monto_pagado', '100')
            ->call('guardar')
            ->assertForbidden();
    }

    public function test_envio_masivo_a_junk_car_con_seleccion_multiple(): void
    {
        $admin = $this->usuarioConRol('admin');

        $uno = Vehicle::factory()->enEstado(EstadoVehiculo::Comprado)->create();
        $dos = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();
        $intacto = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        Livewire::actingAs($admin)
            ->test(EnvioJunkCar::class)
            ->set('seleccion', [$uno->id, $dos->id])
            ->call('enviar')
            ->assertHasNoErrors();

        $this->assertSame(EstadoVehiculo::Desguace, $uno->fresh()->estado);
        $this->assertSame(EstadoVehiculo::Desguace, $dos->fresh()->estado);
        $this->assertSame(EstadoVehiculo::Listo, $intacto->fresh()->estado);

        // Se crea el registro de Junk car; monto y empresa se completan luego.
        $this->assertDatabaseHas('scrap_records', ['vehicle_id' => $uno->id, 'monto_recibido' => null]);
        $this->assertSame(2, \App\Models\ScrapRecord::count());
    }

    public function test_gruero_no_decide_que_vehiculo_va_a_junk_car(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $suyo = Vehicle::factory()->create(['asignado_a' => $gruero->id]);

        $this->assertFalse($gruero->can('enviar a junk'));
        $this->actingAs($gruero)->get('/junk-car')->assertForbidden();

        $this->assertNotSame(EstadoVehiculo::Desguace, $suyo->fresh()->estado);
    }

    public function test_gruero_completa_catalizador_y_monto_del_junk_car(): void
    {
        $admin = $this->usuarioConRol('admin');
        $gruero = $this->usuarioConRol('gruero');
        $vehiculo = Vehicle::factory()->create(['asignado_a' => $gruero->id]);

        // El Admin lo envía a Junk car…
        Livewire::actingAs($admin)
            ->test(EnvioJunkCar::class)
            ->set('seleccion', [$vehiculo->id])
            ->call('enviar');

        // …y el Gruero completa los datos que solo él ve en la calle.
        Livewire::actingAs($gruero)
            ->test(GestorJunkCar::class, ['vehiculo' => $vehiculo->fresh()])
            ->set('tiene_catalizador', '1')
            ->set('monto_junk', '320')
            ->call('guardar')
            ->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertTrue($vehiculo->tiene_catalizador);
        $this->assertSame('320.00', (string) $vehiculo->desguace->monto_recibido);

        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'junk_car_completado',
        ]);
    }

    public function test_gruero_no_completa_junk_car_de_un_vehiculo_ajeno(): void
    {
        $admin = $this->usuarioConRol('admin');
        $gruero = $this->usuarioConRol('gruero');
        $ajeno = Vehicle::factory()->create(['asignado_a' => $this->usuarioConRol('gruero')->id]);

        Livewire::actingAs($admin)
            ->test(EnvioJunkCar::class)
            ->set('seleccion', [$ajeno->id])
            ->call('enviar');

        Livewire::actingAs($gruero)
            ->test(GestorJunkCar::class, ['vehiculo' => $ajeno->fresh()])
            ->set('tiene_catalizador', '1')
            ->set('monto_junk', '100')
            ->call('guardar')
            ->assertForbidden();
    }

    public function test_mecanico_y_vendedor_no_acceden_al_envio_masivo(): void
    {
        $this->actingAs($this->usuarioConRol('mecanico'))->get('/junk-car')->assertForbidden();
        $this->actingAs($this->usuarioConRol('vendedor'))->get('/junk-car')->assertForbidden();
        $this->actingAs($this->usuarioConRol('gruero'))->get('/junk-car')->assertForbidden();
    }
}
