<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\AsignarRecojo;
use App\Livewire\Vehiculos\FormularioVehiculo;
use App\Livewire\Vehiculos\GestorFotosDocumentos;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El VIN se puede repetir: el mismo auto puede volver a entrar (se vendió y se
 * recompró, o se borró y se vuelve a cargar). Antes se bloqueaba; ahora se
 * avisa con un enlace a los otros vehículos con ese VIN y se deja guardar.
 */
class VinRepetidoTest extends TestCase
{
    use RefreshDatabase;

    private const VIN = '1HGCM82633A004352';

    private const AVISO = 'Este VIN ya está en otro vehículo:';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    /** Un vehículo con el VIN de la prueba y un nombre que no se repite. */
    private function conElVin(string $marca, string $modelo, EstadoVehiculo $estado = EstadoVehiculo::Listo): Vehicle
    {
        return Vehicle::factory()->enEstado($estado)
            ->create(['vin' => self::VIN, 'marca' => $marca, 'modelo' => $modelo, 'anio' => 2015]);
    }

    private function formularioCompleto(User $usuario)
    {
        return Livewire::actingAs($usuario)
            ->test(FormularioVehiculo::class)
            ->set('marca', 'Ford')
            ->set('modelo', 'Focus')
            ->set('anio', '2012')
            ->set('millas', '90000')
            ->set('precio_compra', '1800')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('ubicacion_destino', 'casa_hugo')
            ->set('estado_titulo', 'rebuild');
    }

    public function test_el_formulario_avisa_y_deja_guardar_un_vin_repetido(): void
    {
        $existente = $this->conElVin('Honda', 'Accord');

        $this->formularioCompleto($this->usuarioConRol('admin'))
            ->set('vin', strtolower(self::VIN)) // se pasa a mayúsculas al salir del campo
            ->assertSee(self::AVISO)
            ->assertSee('2015 Honda Accord')
            ->assertSeeHtml('href="'.route('vehiculos.ficha', $existente).'"')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(2, Vehicle::where('vin', self::VIN)->count());
    }

    public function test_al_editar_no_avisa_del_propio_vehiculo(): void
    {
        $vehiculo = $this->conElVin('Honda', 'Accord');

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(FormularioVehiculo::class, ['vehiculo' => $vehiculo])
            ->assertDontSee(self::AVISO);
    }

    public function test_con_un_vin_unico_o_incompleto_no_hay_aviso(): void
    {
        $this->conElVin('Honda', 'Accord');

        $this->formularioCompleto($this->usuarioConRol('admin'))
            ->set('vin', '2T1BURHE0JC123456')
            ->assertDontSee(self::AVISO)
            ->set('vin', substr(self::VIN, 0, 10)) // todavía escribiendo
            ->assertDontSee(self::AVISO);
    }

    public function test_los_vehiculos_eliminados_no_aparecen_en_el_aviso(): void
    {
        // No se ven en ningún lado y su ficha da 404: nombrarlos no ayuda.
        $this->conElVin('Honda', 'Accord')->delete();

        $this->formularioCompleto($this->usuarioConRol('admin'))
            ->set('vin', self::VIN)
            ->assertDontSee(self::AVISO)
            ->call('guardar')
            ->assertHasNoErrors();
    }

    public function test_asignar_recojo_acepta_un_vin_repetido(): void
    {
        $this->conElVin('Honda', 'Accord');
        $gruero = $this->usuarioConRol('gruero');

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(AsignarRecojo::class)
            ->set('marca', 'Toyota')
            ->set('modelo', 'Camry')
            ->set('anio', '2010')
            ->set('vin', self::VIN)
            ->assertSee(self::AVISO)
            ->assertSee('2015 Honda Accord')
            ->set('ubicacion_origen_url', 'https://maps.app.goo.gl/abc123')
            ->set('asignado_a', (string) $gruero->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(2, Vehicle::where('vin', self::VIN)->count());
    }

    public function test_la_ficha_guarda_el_vin_repetido_y_solo_nombra_lo_que_el_usuario_ve(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');
        $propio = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)
            ->create(['marca' => 'Mazda', 'modelo' => 'CX-5', 'anio' => 2018]);
        $visible = $this->conElVin('Honda', 'Accord', EstadoVehiculo::Listo);
        // El vendedor no ve los recién comprados: ni su nombre ni su enlace.
        $oculto = $this->conElVin('Nissan', 'Sentra', EstadoVehiculo::Comprado);

        Livewire::actingAs($vendedor)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $propio])
            ->set('vin', self::VIN)
            ->call('guardarVin')
            ->assertHasNoErrors()
            ->assertSee(self::AVISO)
            ->assertSee('2015 Honda Accord')
            ->assertSeeHtml('href="'.route('vehiculos.ficha', $visible).'"')
            ->assertDontSee('Nissan')
            ->assertDontSeeHtml('href="'.route('vehiculos.ficha', $oculto).'"')
            ->assertSee('También está en 1 vehículo que no podés ver.');

        $this->assertSame(self::VIN, $propio->fresh()->vin);
    }

    public function test_el_formato_del_vin_se_sigue_validando(): void
    {
        $this->formularioCompleto($this->usuarioConRol('admin'))
            ->set('vin', '1HGCM82633A00435') // 16 caracteres
            ->call('guardar')
            ->assertHasErrors(['vin' => 'size']);
    }
}
