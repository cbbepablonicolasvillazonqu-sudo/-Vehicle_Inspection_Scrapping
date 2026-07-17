<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\FichaVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrecioSugeridoTest extends TestCase
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

    public function test_admin_fija_y_quita_el_precio_sugerido_con_auditoria(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();
        $admin = $this->usuarioConRol('admin');

        $componente = Livewire::actingAs($admin)
            ->test(FichaVehiculo::class, ['vehiculo' => $vehiculo])
            ->set('precioSugerido', '4500')
            ->call('guardarPrecioSugerido')
            ->assertHasNoErrors();

        $this->assertSame('4500.00', (string) $vehiculo->fresh()->precio_sugerido);
        $this->assertDatabaseHas('audit_logs', [
            'vehicle_id' => $vehiculo->id,
            'accion' => 'precio_sugerido_actualizado',
            'user_id' => $admin->id,
        ]);

        // Dejarlo vacío lo quita.
        $componente->set('precioSugerido', '')
            ->call('guardarPrecioSugerido')
            ->assertHasNoErrors();

        $this->assertNull($vehiculo->fresh()->precio_sugerido);
    }

    public function test_vendedor_ve_el_precio_sugerido_pero_no_puede_fijarlo(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Publicado)->create([
            'precio_sugerido' => 4500,
        ]);
        $vendedor = $this->usuarioConRol('vendedor');

        // Lo ve en la ficha y como pista en el formulario de venta.
        $this->actingAs($vendedor)
            ->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertSee('Precio de venta sugerido')
            ->assertSee('$4,500.00')
            ->assertSee('Precio sugerido:');

        // Pero no puede fijarlo.
        Livewire::actingAs($vendedor)
            ->test(FichaVehiculo::class, ['vehiculo' => $vehiculo])
            ->set('precioSugerido', '9999')
            ->call('guardarPrecioSugerido')
            ->assertForbidden();

        $this->assertSame('4500.00', (string) $vehiculo->fresh()->precio_sugerido);
    }

    public function test_comprador_tampoco_puede_fijar_el_precio_sugerido(): void
    {
        $vehiculo = Vehicle::factory()->create();

        Livewire::actingAs($this->usuarioConRol('comprador'))
            ->test(FichaVehiculo::class, ['vehiculo' => $vehiculo])
            ->set('precioSugerido', '5000')
            ->call('guardarPrecioSugerido')
            ->assertForbidden();

        $this->assertNull($vehiculo->fresh()->precio_sugerido);
    }

    public function test_valor_invalido_es_rechazado(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(FichaVehiculo::class, ['vehiculo' => $vehiculo])
            ->set('precioSugerido', 'abc')
            ->call('guardarPrecioSugerido')
            ->assertHasErrors(['precioSugerido']);
    }
}
