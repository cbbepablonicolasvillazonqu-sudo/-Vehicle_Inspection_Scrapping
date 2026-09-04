<?php

namespace Tests\Feature;

use App\Enums\UbicacionDestino;
use App\Exports\VehiculosExport;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use App\Services\ServicioEstadoVehiculo;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdiomaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    public function test_el_idioma_por_defecto_es_espanol(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->get('/panel')
            ->assertOk()
            ->assertSee('Total invertido en inventario actual')
            ->assertDontSee('Total invested in current inventory');
    }

    public function test_usuario_puede_cambiar_a_ingles_y_persiste(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)
            ->from('/panel')
            ->get('/idioma/en')
            ->assertRedirect('/panel');

        // Persistió en el perfil del usuario.
        $this->assertSame('en', $user->fresh()->locale);

        // Y la interfaz sale en inglés.
        $this->actingAs($user)->get('/panel')
            ->assertSee('Total invested in current inventory')
            ->assertDontSee('Total invertido en inventario actual');
    }

    public function test_idioma_no_soportado_cae_al_por_defecto(): void
    {
        $user = User::factory()->create()->assignRole('vendedor');

        $this->actingAs($user)
            ->from('/panel')
            ->get('/idioma/fr')
            ->assertRedirect('/panel');

        $this->assertSame('es', $user->fresh()->locale);
    }

    public function test_invitado_puede_cambiar_idioma_en_el_login(): void
    {
        $this->from('/login')->get('/idioma/en')->assertRedirect('/login');

        // La sesión recuerda el idioma para la siguiente vista de invitado.
        $this->get('/login')
            ->assertSee('Log in')
            ->assertDontSee('Iniciar sesión');
    }

    /* ------------------ Todo lo que no vive en las vistas ------------------ */

    /**
     * Los lugares son el caso que dio origen a esta prueba: el enum devolvía el
     * texto en español sin pasar por __(), así que salían en español aunque la
     * interfaz estuviera en inglés.
     */
    public function test_las_ubicaciones_se_traducen(): void
    {
        $this->assertSame('OFICINA 1 ALDI', UbicacionDestino::Oficina1Aldi->etiqueta());
        $this->assertSame('CASA HUGO', UbicacionDestino::CasaHugo->etiqueta());

        app()->setLocale('en');

        $this->assertSame('ALDI OFFICE 1', UbicacionDestino::Oficina1Aldi->etiqueta());
        $this->assertSame("HUGO'S HOUSE", UbicacionDestino::CasaHugo->etiqueta());
    }

    /** El título de la pestaña sale del atributo #[Title], que no puede llamar a __(). */
    public function test_el_titulo_de_la_pagina_se_traduce(): void
    {
        $admin = User::factory()->create(['locale' => 'en'])->assignRole('admin');

        $this->actingAs($admin)->get('/vehiculos')
            ->assertOk()
            ->assertSee('<title>Vehicles ·', false)
            ->assertDontSee('<title>Vehículos', false);
    }

    /** El archivo que se descarga tiene que salir en el idioma del usuario. */
    public function test_los_encabezados_de_la_exportacion_se_traducen(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->assertContains('Precio de compra', (new VehiculosExport($admin))->headings());

        app()->setLocale('en');
        $encabezados = (new VehiculosExport($admin))->headings();

        $this->assertContains('Purchase price', $encabezados);
        $this->assertContains('Where it is', $encabezados);
        $this->assertNotContains('Precio de compra', $encabezados);
    }

    /**
     * Las notas que genera el sistema se guardan en español (son la clave) y se
     * traducen al mostrarlas. Si alguien las tradujera al escribir, la fila
     * quedaría congelada en el idioma de quien hizo la acción.
     */
    public function test_las_notas_del_historial_se_traducen_al_mostrarlas(): void
    {
        $admin = User::factory()->create(['locale' => 'en'])->assignRole('admin');
        $vehiculo = Vehicle::factory()->create();

        app(ServicioEstadoVehiculo::class)->registrarEstadoInicial($admin, $vehiculo);

        $this->assertDatabaseHas('vehicle_status_histories', [
            'vehicle_id' => $vehiculo->id,
            'nota' => 'Registro inicial',
        ]);

        $this->actingAs($admin)->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertSee('Initial record')
            ->assertDontSee('Registro inicial');
    }

    /**
     * El panel de auditoría imprimía en crudo los nombres de campo guardados en
     * el JSON. Ahora se traducen reutilizando el diccionario de validación.
     */
    public function test_el_panel_de_auditoria_traduce_campos_y_valores(): void
    {
        $admin = User::factory()->create(['locale' => 'en'])->assignRole('admin');
        $vehiculo = Vehicle::factory()->create();

        app(ServicioAuditoria::class)->registrar($vehiculo, 'vehiculo_editado', [
            'cambios' => [
                'precio_compra' => ['antes' => '1000.00', 'despues' => '1200.00'],
            ],
        ], $admin);

        app(ServicioAuditoria::class)->registrar($vehiculo, 'recojo_registrado', [
            'destino' => 'casa_hugo',
            'pago' => 'efectivo',
        ], $admin);

        // La nota del sistema es su propia clave; la que escribe una persona no.
        app(ServicioAuditoria::class)->registrar($vehiculo, 'cambio_estado', [
            'nota' => 'Enviado a desguace',
        ], $admin);

        app(ServicioAuditoria::class)->registrar($vehiculo, 'cambio_estado', [
            'nota' => 'se rompió el capot',
        ], $admin);

        $this->actingAs($admin)->get("/vehiculos/{$vehiculo->id}")
            ->assertOk()
            ->assertSee('purchase price')
            ->assertSee("HUGO'S HOUSE")
            ->assertSee('Cash')
            // El valor crudo ya no se imprime como texto. No se comprueba
            // 'casa_hugo' porque Livewire lo incluye en su snapshot del modelo,
            // que no es texto visible.
            ->assertSee('Sent to Junk car')
            ->assertSee('se rompió el capot')
            ->assertDontSee('Enviado a desguace')
            ->assertDontSee('precio_compra');
    }

    /** Barrido en los dos sentidos: cada idioma muestra solo lo suyo. */
    public function test_las_pantallas_principales_no_mezclan_idiomas(): void
    {
        $enEspanol = ['Vehículos', 'Precio de compra', 'CASA HUGO', 'Auditoría del vehículo', 'Usuarios'];
        $enIngles = ['Vehicles', 'Purchase price', "HUGO'S HOUSE", 'Vehicle audit log', 'Users'];

        foreach ([['en', $enEspanol], ['es', $enIngles]] as [$idioma, $noDebeVerse]) {
            $usuario = User::factory()->create(['locale' => $idioma])->assignRole('admin');
            $vehiculo = Vehicle::factory()->create([
                'ubicacion_destino' => UbicacionDestino::CasaHugo,
            ]);

            $rutas = ['/panel', '/vehiculos', "/vehiculos/{$vehiculo->id}", '/usuarios', '/reportes/ganancias'];

            foreach ($rutas as $ruta) {
                $respuesta = $this->actingAs($usuario)->get($ruta)->assertOk();

                foreach ($noDebeVerse as $texto) {
                    $respuesta->assertDontSee($texto);
                }
            }
        }
    }
}
