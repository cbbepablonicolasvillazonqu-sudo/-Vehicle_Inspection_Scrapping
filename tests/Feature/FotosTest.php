<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorFotos;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

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

    public function test_mecanico_previsualiza_quita_una_y_sube_el_resto(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();
        $mecanico = $this->usuarioConRol('mecanico');

        Livewire::actingAs($mecanico)
            ->test(GestorFotos::class, ['vehiculo' => $vehiculo])
            ->set('etapa', 'reparacion')
            ->set('fotos', [
                UploadedFile::fake()->image('frente.jpg'),
                UploadedFile::fake()->image('borrosa.jpg'),
                UploadedFile::fake()->image('motor.jpg'),
            ])
            ->call('quitarSeleccion', 1) // descarta "borrosa" desde la previsualización
            ->call('subir')
            ->assertHasNoErrors();

        $fotos = $vehiculo->fotos()->get();

        $this->assertCount(2, $fotos);
        $this->assertEqualsCanonicalizing(
            ['frente.jpg', 'motor.jpg'],
            $fotos->pluck('nombre_original')->all(),
        );

        foreach ($fotos as $foto) {
            $this->assertSame('reparacion', $foto->etapa->value);
            Storage::disk('public')->assertExists($foto->ruta);
        }

        $this->assertSame(2, $vehiculo->auditoria()->where('accion', 'foto_subida')->count());
    }

    public function test_limpiar_seleccion_descarta_todo_sin_subir(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorFotos::class, ['vehiculo' => $vehiculo])
            ->set('fotos', [UploadedFile::fake()->image('a.jpg')])
            ->call('limpiarSeleccion')
            ->assertSet('fotos', []);

        $this->assertSame(0, $vehiculo->fotos()->count());
    }

    public function test_archivo_que_no_es_imagen_es_rechazado(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorFotos::class, ['vehiculo' => $vehiculo])
            ->set('fotos', [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')])
            ->call('subir')
            ->assertHasErrors(['fotos.0']);

        $this->assertSame(0, $vehiculo->fotos()->count());
    }

    public function test_url_de_foto_usa_el_host_de_la_peticion_no_app_url(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();
        $vehiculo->fotos()->create([
            'etapa' => 'compra',
            'ruta' => "vehiculos/{$vehiculo->id}/compra/demo.jpg",
            'nombre_original' => 'demo.jpg',
            'user_id' => $admin->id,
        ]);

        // Simula la entrada por el túnel: Cloudflare reenvía host y esquema reales.
        $respuesta = $this->actingAs($admin)
            ->withHeaders([
                'X-Forwarded-Host' => 'tunel-demo.trycloudflare.com',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get("/vehiculos/{$vehiculo->id}");

        $respuesta->assertOk();
        // La foto debe apuntar al host por el que entró el usuario, no a APP_URL.
        $respuesta->assertSee("https://tunel-demo.trycloudflare.com/storage/vehiculos/{$vehiculo->id}/compra/demo.jpg", false);
        $respuesta->assertDontSee("http://localhost/storage/vehiculos/{$vehiculo->id}/compra/demo.jpg", false);
    }

    public function test_vehiculo_vendido_bloquea_subida_salvo_admin(): void
    {
        $vendido = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();

        Livewire::actingAs($this->usuarioConRol('mecanico'))
            ->test(GestorFotos::class, ['vehiculo' => $vendido])
            ->set('fotos', [UploadedFile::fake()->image('tarde.jpg')])
            ->call('subir')
            ->assertForbidden();

        Livewire::actingAs($this->usuarioConRol('admin'))
            ->test(GestorFotos::class, ['vehiculo' => $vendido])
            ->set('fotos', [UploadedFile::fake()->image('ok.jpg')])
            ->call('subir')
            ->assertHasNoErrors();

        $this->assertSame(1, $vendido->fotos()->count());
    }
}
