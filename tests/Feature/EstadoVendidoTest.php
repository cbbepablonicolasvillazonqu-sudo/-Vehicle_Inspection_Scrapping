<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorEstado;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El estado "Publicado / en venta" se muestra como "Vendido" (pedido del
 * cliente), y su botón ya no cambia el estado: lleva al formulario de venta.
 *
 * Ya existía un estado "Vendido", el final. Por eso el botón no puede seguir
 * pasando el auto a 'publicado': quedaría un auto que dice "Vendido" sin una
 * venta registrada. El estado cambia recién al registrar la venta.
 */
class EstadoVendidoTest extends TestCase
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

    /* -------------------------------- El nombre ------------------------------- */

    public function test_publicado_se_llama_vendido_en_los_dos_idiomas(): void
    {
        $this->assertSame('Vendido', EstadoVehiculo::Publicado->etiqueta());
        $this->assertSame('Vendido', EstadoVehiculo::Publicado->etiquetaCorta());

        app()->setLocale('en');

        $this->assertSame('Sold', EstadoVehiculo::Publicado->etiqueta());
        $this->assertSame('Sold', EstadoVehiculo::Publicado->etiquetaCorta());
    }

    public function test_el_vendido_real_no_cambia(): void
    {
        $this->assertSame('Vendido', EstadoVehiculo::Vendido->etiqueta());

        app()->setLocale('en');
        $this->assertSame('Sold', EstadoVehiculo::Vendido->etiqueta());
    }

    public function test_el_valor_interno_sigue_siendo_publicado(): void
    {
        // Solo cambia lo que se ve. Los datos guardados no se tocan.
        $this->assertSame('publicado', EstadoVehiculo::Publicado->value);
        $this->assertSame('vendido', EstadoVehiculo::Vendido->value);
    }

    /* -------------------------------- El botón -------------------------------- */

    public function test_en_un_auto_listo_el_boton_vendido_lleva_al_formulario(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        Livewire::actingAs($this->usuarioConRol('vendedor'))
            ->test(GestorEstado::class, ['vehiculo' => $vehiculo])
            ->assertSeeHtml('data-lleva-a="formulario-venta"')
            ->assertSee('Vendido')
            // Ya no pasa el auto a 'publicado': eso lo dejaría diciendo
            // "Vendido" sin una venta registrada.
            ->assertDontSeeHtml("cambiarEstado('publicado')");
    }

    public function test_mostrar_el_boton_no_toca_el_estado(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        Livewire::actingAs($this->usuarioConRol('vendedor'))
            ->test(GestorEstado::class, ['vehiculo' => $vehiculo]);

        $this->assertSame(EstadoVehiculo::Listo, $vehiculo->fresh()->estado);
    }

    public function test_sin_formulario_de_venta_el_boton_vendido_no_aparece(): void
    {
        // En reparación no se puede vender, así que no hay formulario al que
        // llevar. Un "Vendido" que no abre nada sería peor que no tenerlo.
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(GestorEstado::class, ['vehiculo' => $vehiculo])
            ->assertDontSeeHtml('data-lleva-a="formulario-venta"')
            ->assertDontSeeHtml("cambiarEstado('publicado')")
            // Los demás destinos del Admin siguen ahí.
            ->assertSeeHtml("cambiarEstado('listo')");
    }

    public function test_los_demas_botones_de_estado_siguen_funcionando(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(GestorEstado::class, ['vehiculo' => $vehiculo])
            ->assertSeeHtml("cambiarEstado('en_reparacion')")
            ->call('cambiarEstado', 'en_reparacion');

        $this->assertSame(EstadoVehiculo::EnReparacion, $vehiculo->fresh()->estado);
    }

    public function test_la_ficha_tiene_el_formulario_con_el_ancla_del_boton(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        // El botón y el formulario viven en componentes distintos: el ancla
        // es lo que los une, así que tiene que estar en la misma página.
        $this->actingAs($this->usuarioConRol('vendedor'))
            ->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertSee('data-lleva-a="formulario-venta"', false)
            ->assertSee('id="formulario-venta"', false);
    }

    /* ----------------------- Los vendidos no se tocan ------------------------ */

    public function test_un_auto_ya_vendido_sigue_intacto(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();
        $vehiculo->venta()->create([
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_venta' => 5000,
            'nombre_comprador' => 'Cliente real',
            'metodo_pago' => 'zelle',
        ]);

        $admin = $this->usuarioConRol('admin');

        // Estado final: sin botones de estado ni botón "Vendido".
        Livewire::actingAs($admin)
            ->test(GestorEstado::class, ['vehiculo' => $vehiculo])
            ->assertDontSeeHtml('data-lleva-a="formulario-venta"')
            ->assertDontSeeHtml('cambiarEstado(');

        // Sin formulario de venta, porque la venta ya existe.
        $this->actingAs($admin)
            ->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertDontSee('id="formulario-venta"', false);

        $vehiculo->refresh();

        $this->assertSame(EstadoVehiculo::Vendido, $vehiculo->estado);
        $this->assertSame('Cliente real', $vehiculo->venta->nombre_comprador);
        $this->assertSame('5000.00', $vehiculo->venta->precio_venta);
    }
}
