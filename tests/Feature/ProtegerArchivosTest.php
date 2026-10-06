<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El comando que mueve los archivos ya subidos de /storage al disco privado.
 * Se corre en producción sobre los archivos reales del cliente: no puede
 * perder ninguno ni tocar la base.
 */
class ProtegerArchivosTest extends TestCase
{
    use RefreshDatabase;

    private const ARCHIVOS = [
        'vehiculos/14/vehiculo/foto.jpg' => 'foto',
        'vehiculos/14/gasto/recibo.jpg' => 'recibo',
        'vehiculos/14/documentos/titulo.pdf' => 'titulo',
        'ventas/14/contrato.pdf' => 'contrato',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ARCHIVOS as $ruta => $contenido) {
            Storage::disk('public')->put($ruta, $contenido);
        }
    }

    public function test_sin_ejecutar_solo_muestra_lo_que_moveria(): void
    {
        $this->artisan('forte:proteger-archivos')
            ->expectsOutputToContain('Se moverían: 4')
            ->expectsOutputToContain('No se movió nada')
            ->assertSuccessful();

        foreach (array_keys(self::ARCHIVOS) as $ruta) {
            Storage::disk('public')->assertExists($ruta);
            Storage::disk('privado')->assertMissing($ruta);
        }
    }

    public function test_con_ejecutar_mueve_todo_con_su_contenido(): void
    {
        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])
            ->expectsOutputToContain('Movidos: 4')
            ->assertSuccessful();

        foreach (self::ARCHIVOS as $ruta => $contenido) {
            $this->assertSame($contenido, Storage::disk('privado')->get($ruta));
            Storage::disk('public')->assertMissing($ruta);
        }

        // Las carpetas vacías tampoco quedan a la vista.
        Storage::disk('public')->assertMissing(['vehiculos', 'ventas']);
    }

    public function test_se_puede_correr_dos_veces(): void
    {
        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])->assertSuccessful();

        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])
            ->expectsOutputToContain('No hay archivos en /storage para proteger.')
            ->assertSuccessful();

        foreach (self::ARCHIVOS as $ruta => $contenido) {
            $this->assertSame($contenido, Storage::disk('privado')->get($ruta));
        }
    }

    public function test_termina_una_corrida_que_se_corto_antes_de_borrar_el_original(): void
    {
        Storage::disk('privado')->put('vehiculos/14/vehiculo/foto.jpg', 'foto');

        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])
            ->expectsOutputToContain('Movidos: 3')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('vehiculos/14/vehiculo/foto.jpg');
        $this->assertSame('foto', Storage::disk('privado')->get('vehiculos/14/vehiculo/foto.jpg'));
    }

    public function test_no_pisa_un_archivo_distinto_con_la_misma_ruta(): void
    {
        Storage::disk('privado')->put('ventas/14/contrato.pdf', 'otro contrato, mas largo');

        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])
            ->expectsOutputToContain('ventas/14/contrato.pdf')
            ->assertFailed();

        // Los dos quedan como estaban; el resto se movió igual.
        $this->assertSame('contrato', Storage::disk('public')->get('ventas/14/contrato.pdf'));
        $this->assertSame('otro contrato, mas largo', Storage::disk('privado')->get('ventas/14/contrato.pdf'));
        Storage::disk('privado')->assertExists('vehiculos/14/vehiculo/foto.jpg');
    }

    public function test_no_toca_nada_fuera_de_las_carpetas_de_los_usuarios(): void
    {
        Storage::disk('public')->put('otra-cosa/archivo.txt', 'no es de la app');

        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])->assertSuccessful();

        $this->assertSame('no es de la app', Storage::disk('public')->get('otra-cosa/archivo.txt'));
        Storage::disk('privado')->assertMissing('otra-cosa/archivo.txt');
    }

    public function test_no_toca_la_base_de_datos(): void
    {
        $vehiculo = Vehicle::factory()->create();
        $foto = $vehiculo->fotos()->create(['etapa' => 'vehiculo', 'ruta' => 'vehiculos/14/vehiculo/foto.jpg']);
        $antes = DB::table('vehicle_photos')->get()->toArray();

        $this->artisan('forte:proteger-archivos', ['--ejecutar' => true])->assertSuccessful();

        $this->assertEquals($antes, DB::table('vehicle_photos')->get()->toArray());

        // Y la foto se sigue viendo, ahora desde el disco privado.
        $this->seed(RolesYPermisosSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)->get($foto->url())->assertOk();
    }
}
