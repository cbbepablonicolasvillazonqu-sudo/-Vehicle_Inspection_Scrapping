<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorGastos;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las fotos solo existen dentro del módulo de Gastos.
 */
class FotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        Storage::fake('public');
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    private function gastoBase(): array
    {
        return [
            'categoria' => 'reparacion',
            'descripcion' => 'Cambio de frenos',
            'monto' => '150.00',
            'fecha' => now()->toDateString(),
        ];
    }

    public function test_mecanico_registra_un_gasto_con_fotos_previsualizadas(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();
        $mecanico = $this->usuarioConRol('mecanico');

        Livewire::actingAs($mecanico)
            ->test(GestorGastos::class, ['vehiculo' => $vehiculo])
            ->call('nuevo')
            ->set($this->gastoBase())
            ->set('fotos', [
                UploadedFile::fake()->image('frente.jpg'),
                UploadedFile::fake()->image('borrosa.jpg'),
                UploadedFile::fake()->image('motor.jpg'),
            ])
            ->call('quitarSeleccion', 1) // descarta "borrosa" desde la previsualización
            ->call('guardar')
            ->assertHasNoErrors();

        $gasto = $vehiculo->gastos()->sole();
        $fotos = $gasto->fotos()->get();

        $this->assertCount(2, $fotos);
        $this->assertEqualsCanonicalizing(
            ['frente.jpg', 'motor.jpg'],
            $fotos->pluck('nombre_original')->all(),
        );

        foreach ($fotos as $foto) {
            $this->assertSame('gasto', $foto->etapa->value);
            $this->assertSame($gasto->id, $foto->expense_id);
            Storage::disk('public')->assertExists($foto->ruta);
        }
    }

    public function test_gasto_sin_fotos_sigue_siendo_valido(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorGastos::class, ['vehiculo' => $vehiculo])
            ->call('nuevo')
            ->set($this->gastoBase())
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, $vehiculo->gastos()->count());
        $this->assertSame(0, $vehiculo->fotos()->count());
    }

    public function test_archivo_que_no_es_imagen_es_rechazado(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorGastos::class, ['vehiculo' => $vehiculo])
            ->call('nuevo')
            ->set($this->gastoBase())
            ->set('fotos', [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')])
            ->call('guardar')
            ->assertHasErrors(['fotos.0']);

        $this->assertSame(0, $vehiculo->fotos()->count());
    }

    public function test_vendedor_no_puede_subir_fotos_ni_registrar_gastos(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');

        $this->assertFalse($vendedor->can('subir fotos'));
        $this->assertFalse($vendedor->can('registrar gastos'));
    }

    public function test_vehiculo_vendido_bloquea_gastos_y_fotos_salvo_admin(): void
    {
        $vendido = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorGastos::class, ['vehiculo' => $vendido])
            ->call('nuevo')
            ->assertForbidden();

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(GestorGastos::class, ['vehiculo' => $vendido])
            ->call('nuevo')
            ->set($this->gastoBase())
            ->set('fotos', [UploadedFile::fake()->image('ok.jpg')])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(1, $vendido->fotos()->count());
    }

    public function test_admin_sube_la_foto_del_vehiculo_al_crearlo_y_la_reemplaza_al_editar(): void
    {
        $admin = $this->usuarioConRol('admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Vehiculos\FormularioVehiculo::class)
            ->set('marca', 'Toyota')
            ->set('modelo', 'Corolla')
            ->set('anio', '2015')
            ->set('vin', '1HGBH41JXMN109186')
            ->set('millas', '120000')
            ->set('precio_compra', '2500')
            ->set('fecha_compra', now()->format('Y-m-d'))
            ->set('ubicacion_destino', 'oficina_1_aldi')
            ->set('estado_titulo', 'clean')
            ->set('foto', UploadedFile::fake()->image('frente.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $vehiculo = Vehicle::firstOrFail();
        $foto = $vehiculo->fotos()->whereNull('expense_id')->sole();

        $this->assertSame('frente.jpg', $foto->nombre_original);
        $this->assertSame('vehiculo', $foto->etapa->value);
        Storage::disk('public')->assertExists($foto->ruta);
        $rutaVieja = $foto->ruta;

        // Al editar se ve la misma foto y, si se sube otra, reemplaza a la anterior.
        Livewire::actingAs($admin)
            ->test(\App\Livewire\Vehiculos\FormularioVehiculo::class, ['vehiculo' => $vehiculo])
            ->assertSet('marca', 'Toyota')
            ->set('foto', UploadedFile::fake()->image('nueva.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $vehiculo->refresh();
        $fotos = $vehiculo->fotos()->whereNull('expense_id')->get();

        $this->assertCount(1, $fotos);
        $this->assertSame('nueva.jpg', $fotos->first()->nombre_original);
        Storage::disk('public')->assertMissing($rutaVieja);
    }
}
