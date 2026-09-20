<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Matriz de acceso completa: qué ruta abre cada rol, de qué vehículos, y qué
 * permisos tiene asignados.
 *
 * Es la red que faltaba. RolAccesoTest cubre las tarjetas del panel y tres
 * casos sueltos; acá se recorren todas las rutas contra todos los roles, para
 * que un permiso de más en RolesYPermisosSeeder no pase inadvertido.
 *
 * Dos comportamientos que parecen errores y son deliberados:
 *  - El Gruero recibe 403 en /vehiculos pero SÍ abre fichas: tiene el permiso
 *    "ver vehiculos" y no "ver inventario", porque trabaja desde su panel.
 *  - El Vendedor exporta y el Mecánico no: "exportar datos" está solo en admin
 *    y vendedor.
 */
class MatrizAccesoTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = ['admin', 'gruero', 'mecanico', 'vendedor'];

    private Vehicle $vehiculo;

    /** @var array<string, User> */
    private array $usuarios = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);

        $this->vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Listo)->create();

        foreach (self::ROLES as $rol) {
            $this->usuarios[$rol] = User::factory()->create()->assignRole($rol);
        }
    }

    /**
     * Ruta => código esperado por rol.
     *
     * Spatie devuelve 403 cuando falta el permiso, no una redirección: el
     * usuario ya está autenticado, simplemente no le corresponde.
     *
     * @return array<string, array<string, int>>
     */
    private function matriz(): array
    {
        $id = $this->vehiculo->id;

        return [
            //                            admin  gruero  mecanico  vendedor
            '/panel' => ['admin' => 200, 'gruero' => 200, 'mecanico' => 200, 'vendedor' => 200],
            '/perfil' => ['admin' => 200, 'gruero' => 200, 'mecanico' => 200, 'vendedor' => 200],

            '/usuarios' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],
            '/reportes/ganancias' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],
            '/exportar/ganancias' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],

            // Exportar vehículos exige "exportar datos": admin y vendedor.
            '/exportar/vehiculos/csv' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 200],
            '/exportar/vehiculos/xlsx' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 200],

            // El inventario exige "ver inventario", que el Gruero no tiene.
            '/vehiculos' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 200, 'vendedor' => 200],

            '/vehiculos/crear' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],
            "/vehiculos/{$id}/editar" => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],

            '/recojos/asignar' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],
            '/junk-car' => ['admin' => 200, 'gruero' => 403, 'mecanico' => 403, 'vendedor' => 403],
        ];
    }

    public function test_cada_rol_entra_solo_donde_le_corresponde(): void
    {
        foreach ($this->matriz() as $ruta => $esperado) {
            foreach ($esperado as $rol => $codigo) {
                $obtenido = $this->actingAs($this->usuarios[$rol])->get($ruta)->getStatusCode();

                $this->assertSame(
                    $codigo,
                    $obtenido,
                    "El rol {$rol} pidió {$ruta}: se esperaba {$codigo} y respondió {$obtenido}.",
                );
            }
        }
    }

    public function test_el_invitado_no_entra_a_ninguna_ruta_privada(): void
    {
        $rutas = array_keys($this->matriz());
        $rutas[] = "/vehiculos/{$this->vehiculo->id}";

        foreach ($rutas as $ruta) {
            $this->get($ruta)->assertRedirect('/login');
        }
    }

    public function test_las_rutas_abiertas_responden_sin_sesion(): void
    {
        $this->get('/up')->assertOk();
        $this->get('/offline')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/')->assertRedirect('/panel');

        // El cambio de idioma funciona sin sesión (se usa desde el login) y un
        // idioma inexistente no rompe: cae al idioma por defecto.
        $this->get('/idioma/en')->assertRedirect();
        $this->get('/idioma/fr')->assertRedirect();
    }

    /** El formato de exportación es parte del contrato de la ruta. */
    public function test_un_formato_de_exportacion_desconocido_no_existe(): void
    {
        $this->actingAs($this->usuarios['admin'])
            ->get('/exportar/vehiculos/pdf')
            ->assertNotFound();
    }

    /**
     * Alcance de la ficha por estado, según Vehicle::esVisiblePara().
     * El Mecánico trabaja con lo que está en el taller y el Vendedor con lo
     * que se puede vender; ninguno de los dos ve los Junk car.
     */
    public function test_el_alcance_de_la_ficha_depende_del_estado(): void
    {
        $esperado = [
            EstadoVehiculo::Comprado->value => ['admin' => 200, 'mecanico' => 200, 'vendedor' => 403],
            EstadoVehiculo::EnReparacion->value => ['admin' => 200, 'mecanico' => 200, 'vendedor' => 403],
            EstadoVehiculo::Listo->value => ['admin' => 200, 'mecanico' => 200, 'vendedor' => 200],
            EstadoVehiculo::Publicado->value => ['admin' => 200, 'mecanico' => 403, 'vendedor' => 200],
            EstadoVehiculo::Vendido->value => ['admin' => 200, 'mecanico' => 403, 'vendedor' => 200],
            EstadoVehiculo::Desguace->value => ['admin' => 200, 'mecanico' => 403, 'vendedor' => 403],
        ];

        foreach ($esperado as $estado => $porRol) {
            $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::from($estado))->create();

            foreach ($porRol as $rol => $codigo) {
                $obtenido = $this->actingAs($this->usuarios[$rol])
                    ->get("/vehiculos/{$vehiculo->id}")
                    ->getStatusCode();

                $this->assertSame(
                    $codigo,
                    $obtenido,
                    "El rol {$rol} abrió un vehículo en estado {$estado}: se esperaba {$codigo} y respondió {$obtenido}.",
                );
            }
        }
    }

    public function test_el_gruero_solo_abre_los_vehiculos_que_tiene_asignados(): void
    {
        $gruero = $this->usuarios['gruero'];
        $otroGruero = User::factory()->create()->assignRole('gruero');

        $propio = Vehicle::factory()->create(['asignado_a' => $gruero->id]);
        $ajeno = Vehicle::factory()->create(['asignado_a' => $otroGruero->id]);
        $sinAsignar = Vehicle::factory()->create(['asignado_a' => null]);

        $this->actingAs($gruero)->get("/vehiculos/{$propio->id}")->assertOk();
        $this->actingAs($gruero)->get("/vehiculos/{$ajeno->id}")->assertForbidden();
        $this->actingAs($gruero)->get("/vehiculos/{$sinAsignar->id}")->assertForbidden();
    }

    /**
     * El candado de verdad: si alguien le agrega un permiso a un rol, esta
     * prueba falla aunque las rutas sigan respondiendo igual. Es lo que evita
     * que un rol gane atribuciones sin que nadie lo note.
     */
    public function test_los_permisos_de_cada_rol_son_exactamente_los_acordados(): void
    {
        $esperado = [
            'gruero' => [
                'ver vehiculos',
                'registrar recojo',
                'completar junk',
            ],
            'mecanico' => [
                'ver vehiculos',
                'ver inventario',
                'cambiar estado',
                'registrar gastos',
                'subir fotos',
            ],
            'vendedor' => [
                'ver vehiculos',
                'ver inventario',
                'cambiar estado',
                'registrar ventas',
                'exportar datos',
            ],
        ];

        foreach ($esperado as $rol => $permisos) {
            sort($permisos);

            $reales = Role::findByName($rol)->permissions->pluck('name')->sort()->values()->all();

            $this->assertSame($permisos, $reales, "Los permisos del rol {$rol} cambiaron.");
        }

        // El Admin los tiene todos, siempre.
        $this->assertSame(
            Permission::count(),
            Role::findByName('admin')->permissions()->count(),
            'El Admin dejó de tener todos los permisos.',
        );

        // El rol "comprador" se retiró del negocio y no debe volver.
        $this->assertNull(Role::where('name', 'comprador')->first());
    }
}
