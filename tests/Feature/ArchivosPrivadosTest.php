<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorFotosDocumentos;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fotos, documentos, fotos de gastos y contratos: solo con sesión y permiso.
 *
 * Antes se servían desde /storage y el enlace era la llave: quien lo tuviera
 * (reenviado, copiado, en el historial) abría el título o el contrato firmado
 * sin cuenta. Ahora salen por ArchivoController, que pide lo mismo que la
 * sección de la ficha donde se ve cada archivo. Los id son consecutivos, así
 * que se prueba también pedirlos a mano.
 */
class ArchivosPrivadosTest extends TestCase
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

    private function foto(Vehicle $vehiculo, bool $deGasto = false, string $disco = 'privado'): VehiclePhoto
    {
        $gasto = $deGasto
            ? $vehiculo->gastos()->create([
                'categoria' => 'piezas', 'descripcion' => 'Repuesto', 'monto' => 50, 'fecha' => now()->format('Y-m-d'),
            ])
            : null;

        $carpeta = $deGasto ? 'gasto' : 'vehiculo';
        $ruta = "vehiculos/{$vehiculo->id}/{$carpeta}/".uniqid().'.jpg';
        Storage::disk($disco)->put($ruta, 'contenido-de-la-foto');

        return $vehiculo->fotos()->create([
            'expense_id' => $gasto?->id,
            'etapa' => $carpeta,
            'ruta' => $ruta,
            'nombre_original' => 'foto.jpg',
        ]);
    }

    private function documento(Vehicle $vehiculo, string $extension = 'pdf', string $contenido = '%PDF-1.4 titulo'): VehicleDocument
    {
        $ruta = "vehiculos/{$vehiculo->id}/documentos/".uniqid().".{$extension}";
        Storage::disk('privado')->put($ruta, $contenido);

        return $vehiculo->documentos()->create(['ruta' => $ruta, 'nombre_original' => "titulo firmado.{$extension}"]);
    }

    private function contrato(Vehicle $vehiculo): Sale
    {
        $ruta = "ventas/{$vehiculo->id}/".uniqid().'.pdf';
        Storage::disk('privado')->put($ruta, '%PDF-1.4 contrato con datos del comprador');

        return $vehiculo->venta()->create([
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_venta' => 5000,
            'nombre_comprador' => 'Comprador',
            'metodo_pago' => 'zelle',
            'contrato_ruta' => $ruta,
            'contrato_nombre' => 'contrato.pdf',
        ]);
    }

    private function contenido(TestResponse $respuesta): string
    {
        return $respuesta->baseResponse->getFile()->getContent();
    }

    /* ------------------------------ Sin sesión ------------------------------ */

    public function test_sin_sesion_ningun_archivo_se_entrega(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();

        foreach ([
            $this->foto($vehiculo)->url(),
            $this->foto($vehiculo, deGasto: true)->url(),
            $this->documento($vehiculo)->url(),
            $this->contrato($vehiculo)->contratoUrl(),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_las_urls_ya_no_apuntan_a_storage(): void
    {
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();

        $this->assertStringStartsWith('/archivos/fotos/', $this->foto($vehiculo)->url());
        $this->assertStringStartsWith('/archivos/documentos/', $this->documento($vehiculo)->url());
        $this->assertStringStartsWith('/archivos/contratos/', $this->contrato($vehiculo)->contratoUrl());
    }

    /* ------------------------- Cada rol, lo que ve -------------------------- */

    public function test_cada_rol_ve_los_archivos_de_las_secciones_que_ve_en_la_ficha(): void
    {
        $admin = $this->usuarioConRol('admin');
        $gruero = $this->usuarioConRol('gruero');
        $mecanico = $this->usuarioConRol('mecanico');
        $vendedor = $this->usuarioConRol('vendedor');

        // Un vehículo en el taller, asignado al gruero: lo ven los tres primeros.
        $taller = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create(['asignado_a' => $gruero->id]);
        $fotoTaller = $this->foto($taller)->url();
        $gastoTaller = $this->foto($taller, deGasto: true)->url();
        $documentoTaller = $this->documento($taller)->url();

        // Un vehículo vendido: lo ven el admin y el vendedor.
        $vendido = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();
        $fotoVendido = $this->foto($vendido)->url();
        $gastoVendido = $this->foto($vendido, deGasto: true)->url();
        $documentoVendido = $this->documento($vendido)->url();
        $contratoVendido = $this->contrato($vendido)->contratoUrl();

        $esperado = [
            // Las fotos de gastos solo las ve quien ve los gastos (admin y mecánico).
            'admin' => [$admin, [
                $fotoTaller => 200, $gastoTaller => 200, $documentoTaller => 200,
                $fotoVendido => 200, $gastoVendido => 200, $documentoVendido => 200, $contratoVendido => 200,
            ]],
            'gruero' => [$gruero, [
                $fotoTaller => 200, $gastoTaller => 403, $documentoTaller => 200,
                $fotoVendido => 403, $gastoVendido => 403, $documentoVendido => 403, $contratoVendido => 403,
            ]],
            'mecanico' => [$mecanico, [
                $fotoTaller => 200, $gastoTaller => 200, $documentoTaller => 200,
                $fotoVendido => 403, $gastoVendido => 403, $documentoVendido => 403, $contratoVendido => 403,
            ]],
            'vendedor' => [$vendedor, [
                $fotoTaller => 403, $gastoTaller => 403, $documentoTaller => 403,
                $fotoVendido => 200, $gastoVendido => 403, $documentoVendido => 200, $contratoVendido => 200,
            ]],
        ];

        foreach ($esperado as $rol => [$usuario, $casos]) {
            foreach ($casos as $url => $codigo) {
                $this->assertSame(
                    $codigo,
                    $this->actingAs($usuario)->get($url)->getStatusCode(),
                    "{$rol} pidiendo {$url}",
                );
            }
        }
    }

    public function test_sin_el_permiso_de_la_seccion_no_se_ve_aunque_vea_el_vehiculo(): void
    {
        // El permiso se le puede quitar a un rol desde el seeder: el archivo
        // tiene que seguir a la sección, no solo al vehículo.
        $mecanico = $this->usuarioConRol('mecanico');
        $mecanico->roles->first()->revokePermissionTo('completar datos del vehiculo');

        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create();

        $this->actingAs($mecanico->fresh())->get($this->documento($vehiculo)->url())->assertForbidden();
        $this->actingAs($mecanico->fresh())->get($this->foto($vehiculo)->url())->assertOk();
    }

    public function test_los_archivos_de_un_vehiculo_borrado_no_se_entregan(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();
        $foto = $this->foto($vehiculo)->url();
        $documento = $this->documento($vehiculo)->url();

        $vehiculo->delete();

        $this->actingAs($admin)->get($foto)->assertNotFound();
        $this->actingAs($admin)->get($documento)->assertNotFound();
    }

    /* ------------------------------ La respuesta ----------------------------- */

    public function test_entrega_el_archivo_con_su_tipo_y_su_nombre(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $foto = $this->actingAs($admin)->get($this->foto($vehiculo)->url())
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('contenido-de-la-foto', $this->contenido($foto));

        $pdf = $this->actingAs($admin)->get($this->documento($vehiculo)->url())
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $pdf->headers->get('Content-Disposition'));
        $this->assertStringContainsString('titulo firmado.pdf', $pdf->headers->get('Content-Disposition'));
    }

    public function test_un_tipo_que_no_esta_en_la_lista_se_descarga_y_no_se_muestra(): void
    {
        // Las subidas ya filtran el formato; esto cubre el día que algo se cuele.
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();
        $documento = $this->documento($vehiculo, 'html', '<script>alert(1)</script>');

        $respuesta = $this->actingAs($admin)->get($documento->url())
            ->assertOk()
            ->assertHeader('Content-Type', 'application/octet-stream');

        $this->assertStringStartsWith('attachment;', $respuesta->headers->get('Content-Disposition'));
    }

    public function test_el_navegador_revalida_y_el_cdn_no_la_guarda(): void
    {
        $admin = $this->usuarioConRol('admin');
        $url = $this->foto(Vehicle::factory()->create())->url();

        $primera = $this->actingAs($admin)->get($url)->assertOk();
        $cache = $primera->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cache);
        $this->assertStringContainsString('no-cache', $cache);

        // Sin cambios: "no cambió" sin volver a mandar la foto.
        $this->actingAs($admin)
            ->withHeader('If-Modified-Since', $primera->headers->get('Last-Modified'))
            ->get($url)
            ->assertStatus(304);
    }

    public function test_un_archivo_que_no_esta_en_ningun_disco_da_404(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();
        $foto = $vehiculo->fotos()->create(['etapa' => 'vehiculo', 'ruta' => 'vehiculos/1/vehiculo/no-existe.jpg']);

        $this->actingAs($admin)->get($foto->url())->assertNotFound();
    }

    public function test_un_archivo_que_todavia_no_se_movio_se_entrega_desde_el_disco_anterior(): void
    {
        // Así el código nuevo se puede subir antes de mover los archivos.
        $admin = $this->usuarioConRol('admin');
        $foto = $this->foto(Vehicle::factory()->create(), disco: 'public');

        $respuesta = $this->actingAs($admin)->get($foto->url())->assertOk();

        $this->assertSame('contenido-de-la-foto', $this->contenido($respuesta));
    }

    /* --------------------------- Subir y borrar ---------------------------- */

    public function test_lo_que_se_sube_va_al_disco_privado_y_no_a_storage(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        Livewire::actingAs($admin)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo])
            ->set('nuevasFotos', [UploadedFile::fake()->image('auto.jpg')])
            ->set('nuevosDocumentos', [UploadedFile::fake()->create('titulo.pdf', 50, 'application/pdf')]);

        $foto = $vehiculo->fotosVehiculo()->sole();
        $documento = $vehiculo->documentos()->sole();

        Storage::disk('privado')->assertExists([$foto->ruta, $documento->ruta]);
        Storage::disk('public')->assertMissing([$foto->ruta, $documento->ruta]);
    }

    public function test_borrar_elimina_el_archivo_de_los_dos_discos(): void
    {
        // Si quedara una copia en /storage, seguiría abierta con el enlace viejo.
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();
        $foto = $this->foto($vehiculo);
        Storage::disk('public')->put($foto->ruta, 'copia vieja');

        Livewire::actingAs($admin)
            ->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo])
            ->call('eliminarFoto', $foto->id);

        Storage::disk('privado')->assertMissing($foto->ruta);
        Storage::disk('public')->assertMissing($foto->ruta);
    }

    /* --------------------------- Service worker ---------------------------- */

    public function test_el_service_worker_no_guarda_archivos_de_los_usuarios(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // v4: al activarse borra la v3, que tenía fotos de /storage guardadas.
        $this->assertStringContainsString("const CACHE = 'forte-towing-v4';", $sw);

        // Los archivos se dejan pasar antes de la regla de assets: una foto
        // pedida por <img> es de tipo "image" y si no se guardaría igual.
        $salida = strpos($sw, "url.pathname.startsWith('/archivos/') || url.pathname.startsWith('/storage/')");
        $assets = strpos($sw, 'const esAsset');
        $this->assertNotFalse($salida);
        $this->assertLessThan($assets, $salida);

        $reglaAssets = substr($sw, $assets, strpos($sw, ';', $assets) - $assets);
        $this->assertStringNotContainsString('/storage/', $reglaAssets);
        $this->assertStringNotContainsString('/archivos/', $reglaAssets);
    }
}
