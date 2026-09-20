<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorVenta;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioRentabilidad;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El formulario de venta sin campos obligatorios.
 *
 * El cliente pidió poder cerrar la venta y cargar los datos después. Eso abre
 * un riesgo concreto: una venta sin precio no puede convertirse en una pérdida
 * inventada. Es el mismo error que con los Junk car sin monto, que llegó a
 * mostrar una pérdida acumulada de −$15.635 en vez de −$10.
 */
class VentaOpcionalTest extends TestCase
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

    private function publicado(?float $precioCompra = null): Vehicle
    {
        return Vehicle::factory()
            ->enEstado(EstadoVehiculo::Publicado)
            ->create(['precio_compra' => $precioCompra]);
    }

    /** Registra una venta dejando vacío todo lo que no se pase. */
    private function registrar(Vehicle $vehiculo, array $datos)
    {
        $prueba = Livewire::actingAs($this->usuarioConRol('vendedor'))
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo])
            ->set('fecha_venta', '');

        foreach ($datos as $campo => $valor) {
            $prueba->set($campo, $valor);
        }

        return $prueba->call('registrar');
    }

    /* ----------------------------- Lo que se pidió ---------------------------- */

    public function test_se_registra_la_venta_con_un_solo_dato(): void
    {
        $vehiculo = $this->publicado();

        $this->registrar($vehiculo, ['nombre_comprador' => 'Solo el nombre'])->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertSame('Solo el nombre', $vehiculo->venta->nombre_comprador);
        $this->assertNull($vehiculo->venta->precio_venta);
        $this->assertNull($vehiculo->venta->fecha_venta);
        $this->assertNull($vehiculo->venta->telefono_comprador);
        $this->assertNull($vehiculo->venta->metodo_pago);
        $this->assertSame(EstadoVehiculo::Vendido, $vehiculo->estado);
    }

    public function test_el_formulario_en_blanco_no_registra_nada(): void
    {
        $vehiculo = $this->publicado();

        // Ningún campo es obligatorio, pero una venta sin un solo dato sería
        // un registro fantasma nacido de un clic de más.
        $this->registrar($vehiculo, [])->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertNull($vehiculo->venta);
        $this->assertSame(EstadoVehiculo::Publicado, $vehiculo->estado);
    }

    public function test_lo_que_se_escribe_igual_tiene_que_ser_valido(): void
    {
        $vehiculo = $this->publicado();

        // Que nada sea obligatorio no significa que valga cualquier cosa.
        $this->registrar($vehiculo, [
            'email_comprador' => 'no-es-un-correo',
            'precio_venta' => 'mil dolares',
        ])->assertHasErrors(['email_comprador', 'precio_venta']);

        $this->assertNull($vehiculo->fresh()->venta);
    }

    /* ------------------- Que no vuelva la pérdida inventada ------------------- */

    public function test_una_venta_sin_precio_no_inventa_una_perdida(): void
    {
        $vehiculo = $this->publicado(3000);

        $this->registrar($vehiculo, [
            'fecha_venta' => now()->format('Y-m-d'),
            'nombre_comprador' => 'Sin precio todavía',
        ])->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertNull($vehiculo->montoRecuperado(), 'Sin precio, lo recuperado es desconocido, no 0.');
        $this->assertNull($vehiculo->ganancia(), 'Sin precio no hay ganancia ni pérdida que calcular.');
    }

    public function test_una_venta_sin_precio_queda_pendiente_de_valorar(): void
    {
        $vehiculo = $this->publicado(3000);

        $this->registrar($vehiculo, [
            'fecha_venta' => now()->format('Y-m-d'),
            'nombre_comprador' => 'Pendiente',
        ])->assertHasNoErrors();

        $rentabilidad = app(ServicioRentabilidad::class);
        $desde = now()->startOfMonth();
        $hasta = now()->endOfMonth();

        $this->assertCount(0, $rentabilidad->salidasValoradas($desde, $hasta));
        $this->assertSame(0.0, $rentabilidad->gananciaEntre($desde, $hasta), 'No puede restar del total.');
        $this->assertSame(1, $rentabilidad->contarPendientesDeValorar());

        $pendiente = $rentabilidad->salidasPendientes($desde, $hasta)->sole();

        // El motivo distingue venta de Junk car: antes el reporte decía
        // "falta el monto del Junk car" en una fila que era una venta.
        $this->assertSame('sin_precio_venta', $pendiente['motivo']);
        $this->assertSame(__('Falta el precio de venta'), motivoPendiente($pendiente['motivo']));
    }

    public function test_al_cargar_el_precio_la_venta_entra_en_la_ganancia(): void
    {
        $vehiculo = $this->publicado(2000);

        $this->registrar($vehiculo, ['nombre_comprador' => 'A completar'])->assertHasNoErrors();
        $vehiculo->refresh();

        // editar() precargaba los campos sin proteger los nulos, y es
        // justamente el formulario con el que se completa una venta a medias.
        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(GestorVenta::class, ['vehiculo' => $vehiculo])
            ->call('editar')
            ->assertSet('nombre_comprador', 'A completar')
            ->assertSet('precio_venta', null)
            ->assertSet('metodo_pago', null)
            ->set('fecha_venta', now()->format('Y-m-d'))
            ->set('precio_venta', '5000')
            ->set('metodo_pago', 'zelle')
            ->call('actualizar')
            ->assertHasNoErrors();

        $vehiculo->refresh();

        $this->assertSame(3000.0, $vehiculo->ganancia());
        $this->assertSame(0, app(ServicioRentabilidad::class)->contarPendientesDeValorar());
    }

    /* ---------------------- Que nada se caiga con nulos ---------------------- */

    public function test_una_venta_sin_fecha_no_rompe_el_reporte(): void
    {
        $vehiculo = $this->publicado(3000);

        $this->registrar($vehiculo, ['precio_venta' => '5000'])->assertHasNoErrors();

        // Sin fecha no se puede ubicar en un mes, pero el acumulado no puede
        // caerse con un error fatal.
        $salidas = app(ServicioRentabilidad::class)->salidas();

        $this->assertCount(1, $salidas);
        $this->assertNull($salidas->first()['fecha']);
        $this->assertSame('—', fecha($salidas->first()['fecha']));
    }

    public function test_una_venta_sin_fecha_igual_aparece_como_pendiente_del_mes(): void
    {
        $vehiculo = $this->publicado(3000);

        $this->registrar($vehiculo, ['nombre_comprador' => 'Ni fecha ni precio'])->assertHasNoErrors();

        $rentabilidad = app(ServicioRentabilidad::class);

        // El panel la cuenta. Si el reporte del mes la filtrara por fecha, el
        // Admin vería el aviso sin poder encontrar cuál completar.
        $this->assertSame(1, $rentabilidad->contarPendientesDeValorar());

        $delMes = $rentabilidad->salidasPendientes(now()->startOfMonth(), now()->endOfMonth());

        $this->assertCount(1, $delMes);
        $this->assertNull($delMes->first()['fecha']);
    }

    public function test_la_ficha_de_una_venta_a_medias_se_abre_sin_errores(): void
    {
        $vehiculo = $this->publicado();

        $this->registrar($vehiculo, ['nombre_comprador' => 'Media venta'])->assertHasNoErrors();

        // La vista hacía fecha_venta->format() y metodo_pago->etiqueta() sin
        // proteger: con nulos tiraba abajo la página entera.
        $this->actingAs($this->usuarioConRol('admin'))
            ->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertSee('Media venta')
            ->assertSee(__('Sin precio'));
    }

    public function test_el_excel_no_muestra_perdida_en_una_venta_sin_precio(): void
    {
        $vehiculo = $this->publicado(3000);

        $this->registrar($vehiculo, [
            'fecha_venta' => now()->format('Y-m-d'),
            'nombre_comprador' => 'Sin precio',
        ])->assertHasNoErrors();

        $admin = $this->usuarioConRol('admin');
        $export = new \App\Exports\VehiculosExport($admin);
        $fila = array_combine($export->headings(), $export->map($vehiculo->fresh()));

        // Las dos celdas van vacías, nunca en 0 ni en negativo.
        $this->assertNull($fila[__('Recuperado (venta/desguace)')]);
        $this->assertNull($fila[__('Ganancia')]);
    }
}
