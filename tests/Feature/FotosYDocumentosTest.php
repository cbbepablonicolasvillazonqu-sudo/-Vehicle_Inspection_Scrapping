<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\GestorFotosDocumentos;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Factories\VehicleFactory;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fotos, VIN y documentos del vehículo desde la ficha, para todos los roles.
 *
 * Antes solo el Admin podía subir la foto o escribir el VIN, y no había dónde
 * guardar los documentos del vehículo. Cada rol trabaja sobre los vehículos
 * que puede ver, y en un registro bloqueado (vendido o Junk car) se puede
 * seguir subiendo, pero corregir y borrar queda para el Admin.
 */
class FotosYDocumentosTest extends TestCase
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

    /** Un vehículo que ese rol puede ver. */
    private function vehiculoVisiblePara(string $rol, User $usuario): Vehicle
    {
        return match ($rol) {
            'gruero' => Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create(['asignado_a' => $usuario->id]),
            'mecanico' => Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create(),
            'vendedor' => Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create(),
            default => Vehicle::factory()->create(),
        };
    }

    private function gestor(User $usuario, Vehicle $vehiculo)
    {
        return Livewire::actingAs($usuario)->test(GestorFotosDocumentos::class, ['vehiculo' => $vehiculo]);
    }

    private function pdf(string $nombre = 'titulo.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($nombre, 120, 'application/pdf');
    }

    /* --------------------------- Lo que se pidió --------------------------- */

    public function test_todos_los_roles_suben_fotos_documentos_y_corrigen_el_vin(): void
    {
        foreach (['admin', 'gruero', 'mecanico', 'vendedor'] as $rol) {
            $usuario = $this->usuarioConRol($rol);
            $vehiculo = $this->vehiculoVisiblePara($rol, $usuario);
            $vin = VehicleFactory::vinAleatorio();

            $this->gestor($usuario, $vehiculo)
                ->set('nuevasFotos', [UploadedFile::fake()->image("{$rol}.jpg")])
                ->assertHasNoErrors()
                ->set('nuevosDocumentos', [$this->pdf("titulo-{$rol}.pdf")])
                ->assertHasNoErrors()
                ->set('vin', strtolower($vin))
                ->call('guardarVin')
                ->assertHasNoErrors();

            $vehiculo->refresh();

            $this->assertSame(1, $vehiculo->fotosVehiculo()->count(), "Fotos del rol {$rol}");
            $this->assertSame(1, $vehiculo->documentos()->count(), "Documentos del rol {$rol}");
            $this->assertSame($vin, $vehiculo->vin, "El VIN del rol {$rol} se guarda en mayúsculas");
        }
    }

    public function test_la_ficha_muestra_la_seccion_a_todos_los_roles(): void
    {
        foreach (['admin', 'gruero', 'mecanico', 'vendedor'] as $rol) {
            $usuario = $this->usuarioConRol($rol);
            $vehiculo = $this->vehiculoVisiblePara($rol, $usuario);

            $this->actingAs($usuario)
                ->get("/vehiculos/{$vehiculo->id}")
                ->assertOk()
                ->assertSee(__('Fotos, VIN y documentos'));
        }
    }

    public function test_los_botones_de_tomar_foto_abren_la_camara(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $html = $this->actingAs($admin)->get("/vehiculos/{$vehiculo->id}")->assertOk()->getContent();

        // Uno para las fotos y otro para los documentos. Sin capture, en
        // Android un campo que acepta PDF abre el gestor de archivos y no la cámara.
        $this->assertSame(2, substr_count($html, 'capture="environment"'));
        $this->assertStringContainsString('accept="image/*,application/pdf"', $html);
    }

    public function test_nadie_toca_un_vehiculo_que_no_puede_ver(): void
    {
        $casos = [
            // El mecánico no ve lo que ya está en venta.
            [$this->usuarioConRol('mecanico'), Vehicle::factory()->enEstado(EstadoVehiculo::Publicado)->create()],
            // El vendedor no ve lo que está en el taller.
            [$this->usuarioConRol('vendedor'), Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create()],
            // El gruero solo ve lo que tiene asignado.
            [$this->usuarioConRol('gruero'), Vehicle::factory()->enEstado(EstadoVehiculo::EnReparacion)->create()],
        ];

        foreach ($casos as [$usuario, $vehiculo]) {
            $this->gestor($usuario, $vehiculo)
                ->set('nuevasFotos', [UploadedFile::fake()->image('ajena.jpg')])
                ->assertForbidden();

            $this->gestor($usuario, $vehiculo)
                ->set('nuevosDocumentos', [$this->pdf()])
                ->assertForbidden();

            $this->gestor($usuario, $vehiculo)
                ->set('vin', VehicleFactory::vinAleatorio())
                ->call('guardarVin')
                ->assertForbidden();

            $this->assertSame(0, $vehiculo->fotosVehiculo()->count());
            $this->assertSame(0, $vehiculo->documentos()->count());
        }
    }

    /* --------------------------------- VIN --------------------------------- */

    public function test_el_vin_mantiene_sus_reglas(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();
        $otro = Vehicle::factory()->create();
        $borrado = Vehicle::factory()->create();
        $borrado->delete();

        foreach ([
            '1HGBH41JXMN10918' => '16 caracteres',
            '1HGBH41JXMN1O9186' => 'tiene la letra O',
            $otro->vin => 'es de otro vehículo',
            // El arreglo de agosto: un vehículo borrado sigue ocupando su VIN.
            $borrado->vin => 'es de un vehículo borrado',
        ] as $vin => $motivo) {
            $this->gestor($vendedor, $vehiculo)
                ->set('vin', $vin)
                ->call('guardarVin')
                ->assertHasErrors('vin');

            $this->assertNotSame($vin, $vehiculo->fresh()->vin, "Se aceptó un VIN que {$motivo}");
        }
    }

    /**
     * La regla en sí, sin la red de seguridad. La prueba de arriba pasaría
     * igual si alguien volviera a poner withoutTrashed(): el choque contra la
     * base terminaría en el mismo mensaje. Esta fija que la regla no se rompa.
     */
    public function test_la_regla_del_vin_cuenta_los_vehiculos_borrados(): void
    {
        $borrado = Vehicle::factory()->create();
        $borrado->delete();

        $validador = \Illuminate\Support\Facades\Validator::make(
            ['vin' => $borrado->vin],
            ['vin' => Vehicle::reglasVin()],
        );

        $this->assertTrue($validador->fails(), 'La regla dejó pasar el VIN de un vehículo borrado.');
    }

    public function test_el_cambio_de_vin_queda_en_la_auditoria(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $vehiculo = $this->vehiculoVisiblePara('gruero', $gruero);
        $anterior = $vehiculo->vin;
        $nuevo = VehicleFactory::vinAleatorio();

        $this->gestor($gruero, $vehiculo)
            ->set('vin', $nuevo)
            ->call('guardarVin')
            ->assertHasNoErrors()
            ->assertDispatched('vehiculo-actualizado');

        $registro = AuditLog::where('vehicle_id', $vehiculo->id)->where('accion', 'vehiculo_editado')->sole();

        $this->assertSame($gruero->id, $registro->user_id);
        $this->assertSame(['antes' => $anterior, 'despues' => $nuevo], $registro->detalles['cambios']['vin']);
    }

    public function test_una_carrera_por_el_mismo_vin_no_devuelve_un_500(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();
        $vin = VehicleFactory::vinAleatorio();

        // Otra persona guarda el mismo VIN justo entre la validación y el UPDATE.
        $yaCorrio = false;
        Vehicle::updating(function () use (&$yaCorrio, $vin) {
            if ($yaCorrio) {
                return;
            }

            $yaCorrio = true;

            DB::table('vehicles')->insert([
                'marca' => 'Otro', 'modelo' => 'Usuario', 'anio' => 2015, 'vin' => $vin,
                'estado' => 'comprado', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->gestor($vendedor, $vehiculo)
            ->set('vin', $vin)
            ->call('guardarVin')
            ->assertHasErrors(['vin' => [__('validation.custom.vin.unique')]]);
    }

    /* ------------------------------- Archivos ------------------------------ */

    public function test_los_documentos_aceptan_pdf_e_imagen_y_rechazan_lo_demas(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $this->gestor($admin, $vehiculo)
            ->set('nuevosDocumentos', [$this->pdf(), UploadedFile::fake()->image('registro.jpg')])
            ->assertHasNoErrors();

        $this->gestor($admin, $vehiculo)
            ->set('nuevosDocumentos', [UploadedFile::fake()->create('malicioso.exe', 10, 'application/x-msdownload')])
            ->assertHasErrors(['nuevosDocumentos.0']);

        $documentos = $vehiculo->documentos()->orderBy('id')->get();

        $this->assertCount(2, $documentos);
        $this->assertTrue($documentos[0]->esPdf());
        $this->assertFalse($documentos[1]->esPdf());

        foreach ($documentos as $documento) {
            Storage::disk('public')->assertExists($documento->ruta);
            $this->assertStringStartsWith("vehiculos/{$vehiculo->id}/documentos/", $documento->ruta);
        }
    }

    public function test_las_fotos_rechazan_pdf_y_heic_con_un_mensaje_claro(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $this->gestor($admin, $vehiculo)
            ->set('nuevasFotos', [$this->pdf()])
            ->assertHasErrors(['nuevasFotos.0']);

        $this->gestor($admin, $vehiculo)
            ->set('nuevasFotos', [UploadedFile::fake()->create('camara.heic', 200, 'image/heic')])
            ->assertHasErrors(['nuevasFotos.0' => [__('La foto tiene que ser JPG, PNG o WEBP. Si tu cámara guarda en HEIC, cambiala a JPG en sus ajustes.')]]);

        $this->assertSame(0, $vehiculo->fotosVehiculo()->count());
    }

    public function test_se_pueden_subir_varias_fotos_a_la_vez(): void
    {
        $mecanico = $this->usuarioConRol('mecanico');
        $vehiculo = $this->vehiculoVisiblePara('mecanico', $mecanico);

        $this->gestor($mecanico, $vehiculo)
            ->set('nuevasFotos', [
                UploadedFile::fake()->image('frente.jpg'),
                UploadedFile::fake()->image('costado.jpg'),
                UploadedFile::fake()->image('interior.jpg'),
            ])
            ->assertHasNoErrors()
            ->assertSet('nuevasFotos', []);

        $this->assertSame(3, $vehiculo->fotosVehiculo()->count());
    }

    /* ------------------------------- Portada ------------------------------- */

    public function test_sin_elegir_la_portada_es_la_primera_foto(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $this->gestor($admin, $vehiculo)
            ->set('nuevasFotos', [UploadedFile::fake()->image('primera.jpg')])
            ->set('nuevasFotos', [UploadedFile::fake()->image('un-rayon.jpg')]);

        // La última no pasa a ser la portada solo por ser la última.
        $this->assertSame('primera.jpg', $vehiculo->fresh()->fotoPortada->nombre_original);
    }

    public function test_se_elige_la_portada_y_si_se_borra_vuelve_a_la_primera(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $gestor = $this->gestor($admin, $vehiculo)
            ->set('nuevasFotos', [
                UploadedFile::fake()->image('primera.jpg'),
                UploadedFile::fake()->image('la-buena.jpg'),
            ]);

        $buena = $vehiculo->fotosVehiculo()->where('nombre_original', 'la-buena.jpg')->sole();

        $gestor->call('marcarPortada', $buena->id)->assertDispatched('vehiculo-actualizado');
        $this->assertSame('la-buena.jpg', $vehiculo->fresh()->fotoPortada->nombre_original);

        $gestor->call('eliminarFoto', $buena->id);
        $this->assertSame('primera.jpg', $vehiculo->fresh()->fotoPortada->nombre_original);
        Storage::disk('public')->assertMissing($buena->ruta);
    }

    public function test_los_documentos_y_las_fotos_de_gastos_no_son_portada(): void
    {
        $admin = $this->usuarioConRol('admin');
        $vehiculo = Vehicle::factory()->create();

        $gasto = $vehiculo->gastos()->create([
            'categoria' => 'piezas', 'descripcion' => 'Repuesto', 'monto' => 50, 'fecha' => now()->format('Y-m-d'),
        ]);
        $vehiculo->fotos()->create(['expense_id' => $gasto->id, 'etapa' => 'gasto', 'ruta' => 'x/repuesto.jpg']);

        $this->gestor($admin, $vehiculo)->set('nuevosDocumentos', [UploadedFile::fake()->image('titulo.jpg')]);

        $vehiculo->refresh();

        $this->assertNull($vehiculo->fotoPortada);
        $this->assertSame(0, $vehiculo->fotosVehiculo()->count());
    }

    /* ----------------------- Registros bloqueados --------------------------- */

    public function test_en_un_vendido_se_sube_pero_no_se_corrige_ni_se_borra(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');
        $vendido = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create();

        // Los papeles suelen llegar después de la venta: subir está permitido.
        $this->gestor($vendedor, $vendido)
            ->set('nuevosDocumentos', [$this->pdf('titulo-firmado.pdf')])
            ->assertHasNoErrors();

        $documento = $vendido->documentos()->sole();

        // Pero corregir el VIN, cambiar la portada o borrar es del Admin.
        $this->gestor($vendedor, $vendido)
            ->set('vin', VehicleFactory::vinAleatorio())
            ->call('guardarVin')
            ->assertForbidden();

        $this->gestor($vendedor, $vendido)
            ->call('eliminarDocumento', $documento->id)
            ->assertForbidden();

        $this->assertSame(1, $vendido->documentos()->count());

        // El Admin sí puede.
        $this->gestor($this->usuarioConRol('admin'), $vendido)
            ->call('eliminarDocumento', $documento->id);

        $this->assertSame(0, $vendido->documentos()->count());
    }

    /* ------------------------------ Quién borra ----------------------------- */

    public function test_solo_quien_lo_subio_o_el_admin_puede_borrar(): void
    {
        $mecanico = $this->usuarioConRol('mecanico');
        $otroMecanico = $this->usuarioConRol('mecanico');
        $vehiculo = $this->vehiculoVisiblePara('mecanico', $mecanico);

        $this->gestor($mecanico, $vehiculo)->set('nuevasFotos', [UploadedFile::fake()->image('mia.jpg')]);
        $foto = $vehiculo->fotosVehiculo()->sole();

        $this->gestor($otroMecanico, $vehiculo)
            ->call('eliminarFoto', $foto->id)
            ->assertForbidden();

        $this->gestor($mecanico, $vehiculo)->call('eliminarFoto', $foto->id);

        $this->assertSame(0, $vehiculo->fotosVehiculo()->count());
        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'foto_eliminada']);
    }

    public function test_cada_subida_queda_en_la_auditoria(): void
    {
        $gruero = $this->usuarioConRol('gruero');
        $vehiculo = $this->vehiculoVisiblePara('gruero', $gruero);

        $this->gestor($gruero, $vehiculo)
            ->set('nuevasFotos', [UploadedFile::fake()->image('frente.jpg')])
            ->set('nuevosDocumentos', [$this->pdf()]);

        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'foto_subida', 'user_id' => $gruero->id]);
        $this->assertDatabaseHas('audit_logs', ['vehicle_id' => $vehiculo->id, 'accion' => 'documento_subido', 'user_id' => $gruero->id]);
    }
}
