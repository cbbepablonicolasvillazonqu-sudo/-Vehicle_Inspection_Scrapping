<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorFotosDocumentos;
use App\Livewire\Vehiculos\GestorGastos;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Services\ServicioArchivos;
use App\Services\ServicioFotos;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Las fotos se guardan reducidas en WebP y con miniatura.
 *
 * Las fotos de celular (4032x3024, unos 4 MB) se mostraban enteras en
 * tarjetas de 112 px: una página de la lista bajaba 46 MB y el scroll se
 * trababa. El original no se conserva: lo decidió el cliente.
 */
class FotosOptimizadasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        Storage::fake('privado');
        Storage::fake('public');
        $this->admin = User::factory()->create()->assignRole('admin');
    }

    /**
     * JPEG con la mitad izquierda roja y la derecha azul. Con $orientacion
     * lleva la nota EXIF que pone el celular al sacar una foto vertical.
     */
    private function jpeg(int $ancho, int $alto, ?int $orientacion = null, bool $intel = false): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);
        imagefilledrectangle($imagen, 0, 0, intdiv($ancho, 2) - 1, $alto - 1, imagecolorallocate($imagen, 255, 0, 0));
        imagefilledrectangle($imagen, intdiv($ancho, 2), 0, $ancho - 1, $alto - 1, imagecolorallocate($imagen, 0, 0, 255));
        ob_start();
        imagejpeg($imagen, null, 90);
        $jpeg = ob_get_clean();

        if ($orientacion === null) {
            return $jpeg;
        }

        // EXIF mínimo con un solo dato, la orientación. iPhone escribe en
        // orden "MM" (big endian) y muchos Android en "II" (little endian).
        $tiff = $intel
            ? "II\x2A\x00\x08\x00\x00\x00"."\x01\x00"."\x12\x01\x03\x00\x01\x00\x00\x00".pack('v', $orientacion)."\x00\x00"."\x00\x00\x00\x00"
            : "MM\x00\x2A\x00\x00\x00\x08"."\x00\x01"."\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientacion)."\x00\x00"."\x00\x00\x00\x00";
        $app1 = "Exif\x00\x00".$tiff;

        return "\xFF\xD8\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
    }

    private function subir(Vehicle $vehiculo, string $contenido, string $nombre = 'IMG_0001.jpg'): VehiclePhoto
    {
        Livewire::actingAs($this->admin)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo])
            ->set('nuevasFotos', [UploadedFile::fake()->createWithContent($nombre, $contenido)])
            ->assertHasNoErrors();

        return $vehiculo->fotosVehiculo()->latest('id')->firstOrFail();
    }

    /** [ancho, alto, tipo] de un archivo del disco privado. */
    private function medidas(string $ruta): array
    {
        return array_slice(getimagesizefromstring(Storage::disk('privado')->get($ruta)), 0, 3);
    }

    /** ¿El color en ese punto es rojo? (WebP es con pérdida: se compara con margen). */
    private function esRojo(string $ruta, float $x, float $y): bool
    {
        $imagen = imagecreatefromstring(Storage::disk('privado')->get($ruta));
        $color = imagecolorat($imagen, (int) (imagesx($imagen) * $x), (int) (imagesy($imagen) * $y));

        return (($color >> 16) & 255) > 180 && ($color & 255) < 80;
    }

    /* ------------------------------- Subida -------------------------------- */

    public function test_la_foto_de_celular_se_guarda_reducida_en_webp_y_con_miniatura(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $foto = $this->subir($vehiculo, $this->jpeg(3000, 2000));

        $this->assertSame([2560, 1707, IMAGETYPE_WEBP], $this->medidas($foto->ruta));
        $this->assertSame([720, 480, IMAGETYPE_WEBP], $this->medidas($foto->ruta_miniatura));
        $this->assertSame('IMG_0001.jpg', $foto->nombre_original);

        // El original no se guarda: solo quedan la foto y su miniatura.
        $this->assertCount(2, Storage::disk('privado')->allFiles("vehiculos/{$vehiculo->id}"));
    }

    public function test_una_foto_vertical_queda_derecha(): void
    {
        // Orientación 6: el sensor la guardó acostada y hay que girarla 90°
        // a la derecha. La mitad izquierda (roja) tiene que quedar arriba.
        $foto = $this->subir(Vehicle::factory()->create(), $this->jpeg(1200, 900, orientacion: 6));

        $this->assertSame([900, 1200, IMAGETYPE_WEBP], $this->medidas($foto->ruta));
        $this->assertSame([480, 640, IMAGETYPE_WEBP], $this->medidas($foto->ruta_miniatura));
        $this->assertTrue($this->esRojo($foto->ruta, 0.5, 0.25), 'arriba tendría que ser rojo');
        $this->assertFalse($this->esRojo($foto->ruta, 0.5, 0.75), 'abajo tendría que ser azul');
        $this->assertTrue($this->esRojo($foto->ruta_miniatura, 0.5, 0.25));
    }

    public function test_la_rotacion_se_lee_en_el_orden_de_bytes_de_android(): void
    {
        // Orientación 8, en "II": girar 90° a la izquierda. El rojo va abajo.
        $foto = $this->subir(Vehicle::factory()->create(), $this->jpeg(1200, 900, orientacion: 8, intel: true));

        $this->assertSame([900, 1200, IMAGETYPE_WEBP], $this->medidas($foto->ruta));
        $this->assertFalse($this->esRojo($foto->ruta, 0.5, 0.25));
        $this->assertTrue($this->esRojo($foto->ruta, 0.5, 0.75));
    }

    public function test_una_foto_chica_no_se_agranda(): void
    {
        $foto = $this->subir(Vehicle::factory()->create(), $this->jpeg(300, 200));

        $this->assertSame([300, 200, IMAGETYPE_WEBP], $this->medidas($foto->ruta));
        $this->assertSame([300, 200, IMAGETYPE_WEBP], $this->medidas($foto->ruta_miniatura));
    }

    public function test_las_fotos_de_los_gastos_tambien_se_optimizan(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        Livewire::actingAs($this->admin)
            ->test(GestorGastos::class, ['vehiculo' => $vehiculo])
            ->call('nuevo')
            ->set(['categoria' => 'piezas', 'descripcion' => 'Repuesto', 'monto' => '80', 'fecha' => now()->toDateString()])
            ->set('fotos', [UploadedFile::fake()->createWithContent('recibo.jpg', $this->jpeg(3000, 2000))])
            ->call('guardar')
            ->assertHasNoErrors();

        $foto = $vehiculo->gastos()->sole()->fotos()->sole();

        $this->assertSame([2560, 1707, IMAGETYPE_WEBP], $this->medidas($foto->ruta));
        $this->assertNotNull($foto->ruta_miniatura);
    }

    public function test_si_la_foto_no_se_puede_procesar_se_guarda_entera_como_antes(): void
    {
        // Por ejemplo, una foto tan grande que no entra en la memoria de PHP.
        $this->instance(ServicioFotos::class, Mockery::mock(ServicioFotos::class, [app(ServicioArchivos::class)])
            ->makePartial()
            ->shouldReceive('procesar')->andReturnNull()->getMock());

        $foto = $this->subir(Vehicle::factory()->create(), $this->jpeg(3000, 2000));

        $this->assertStringEndsWith('.jpg', $foto->ruta);
        $this->assertNull($foto->ruta_miniatura);
        $this->assertSame([3000, 2000, IMAGETYPE_JPEG], $this->medidas($foto->ruta));
        $this->assertSame($foto->url(), $foto->urlMiniatura());
    }

    /* ----------------------------- Al mostrarla ---------------------------- */

    public function test_la_lista_y_la_galeria_muestran_la_miniatura_y_el_enlace_abre_la_foto(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $foto = $this->subir($vehiculo, $this->jpeg(3000, 2000));

        $this->actingAs($this->admin)->get('/vehiculos')
            ->assertSeeHtml('src="'.$foto->urlMiniatura().'"');

        Livewire::actingAs($this->admin)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo])
            ->assertSeeHtml('src="'.$foto->urlMiniatura().'"')
            ->assertSeeHtml('href="'.$foto->url().'"');
    }

    public function test_la_miniatura_se_entrega_con_los_mismos_permisos_que_la_foto(): void
    {
        $foto = $this->subir(Vehicle::factory()->enEstado(EstadoVehiculo::Comprado)->create(), $this->jpeg(3000, 2000));

        $respuesta = $this->actingAs($this->admin)->get($foto->urlMiniatura())
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');
        $this->assertSame(
            Storage::disk('privado')->get($foto->ruta_miniatura),
            $respuesta->baseResponse->getFile()->getContent(),
        );

        // El vendedor no ve vehículos recién comprados: tampoco su miniatura.
        $vendedor = User::factory()->create()->assignRole('vendedor');
        $this->actingAs($vendedor)->get($foto->urlMiniatura())->assertForbidden();

        auth()->logout();
        $this->get($foto->urlMiniatura())->assertRedirect('/login');
    }

    public function test_al_descargarla_el_nombre_lleva_la_extension_real(): void
    {
        $foto = $this->subir(Vehicle::factory()->create(), $this->jpeg(300, 200), 'IMG_0001.jpg');

        $disposicion = $this->actingAs($this->admin)->get($foto->url())->headers->get('Content-Disposition');

        // Con .jpg, el sistema del celular no sabría abrir un archivo WebP.
        $this->assertStringContainsString('IMG_0001.webp', $disposicion);
    }

    public function test_borrar_la_foto_borra_tambien_la_miniatura(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $foto = $this->subir($vehiculo, $this->jpeg(300, 200));

        Livewire::actingAs($this->admin)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo])
            ->call('eliminarFoto', $foto->id);

        Storage::disk('privado')->assertMissing([$foto->ruta, $foto->ruta_miniatura]);
    }

    /* ------------------------- Fotos ya subidas ---------------------------- */

    public function test_el_comando_optimiza_las_fotos_viejas_y_borra_el_original(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $vieja = $vehiculo->fotos()->create(['etapa' => 'vehiculo', 'ruta' => "vehiculos/{$vehiculo->id}/vehiculo/vieja.jpg"]);
        Storage::disk('privado')->put($vieja->ruta, $this->jpeg(3000, 2000));

        // Una que todavía está en /storage, de antes de proteger los archivos.
        $enPublic = $vehiculo->fotos()->create(['etapa' => 'vehiculo', 'ruta' => "vehiculos/{$vehiculo->id}/vehiculo/en-public.jpg"]);
        Storage::disk('public')->put($enPublic->ruta, $this->jpeg(1200, 900, orientacion: 6));

        // Sin --ejecutar no toca nada.
        $this->artisan('forte:optimizar-fotos')
            ->expectsOutputToContain('Se optimizarían: 2')
            ->assertSuccessful();
        $this->assertNull($vieja->fresh()->ruta_miniatura);
        Storage::disk('privado')->assertExists($vieja->ruta);

        $this->artisan('forte:optimizar-fotos', ['--ejecutar' => true])
            ->expectsOutputToContain('Optimizadas: 2')
            ->assertSuccessful();

        $nueva = $vieja->fresh();
        $this->assertSame([2560, 1707, IMAGETYPE_WEBP], $this->medidas($nueva->ruta));
        $this->assertSame([720, 480, IMAGETYPE_WEBP], $this->medidas($nueva->ruta_miniatura));
        $this->assertStringStartsWith("vehiculos/{$vehiculo->id}/vehiculo/", $nueva->ruta);
        Storage::disk('privado')->assertMissing($vieja->ruta);

        // La de /storage queda derecha, en el disco privado, y sin copia pública.
        $this->assertSame([900, 1200, IMAGETYPE_WEBP], $this->medidas($enPublic->fresh()->ruta));
        Storage::disk('public')->assertMissing($enPublic->ruta);

        // Se puede volver a correr: no queda nada por hacer.
        $this->artisan('forte:optimizar-fotos', ['--ejecutar' => true])
            ->expectsOutputToContain('Todas las fotos ya están optimizadas.')
            ->assertSuccessful();
    }

    public function test_el_comando_avisa_de_las_fotos_que_no_estan_en_el_disco(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $perdida = $vehiculo->fotos()->create(['etapa' => 'vehiculo', 'ruta' => 'vehiculos/1/vehiculo/no-existe.jpg']);

        $this->artisan('forte:optimizar-fotos', ['--ejecutar' => true])
            ->expectsOutputToContain('No están en ningún disco')
            ->assertSuccessful();

        $this->assertSame('vehiculos/1/vehiculo/no-existe.jpg', $perdida->fresh()->ruta);
    }
}
