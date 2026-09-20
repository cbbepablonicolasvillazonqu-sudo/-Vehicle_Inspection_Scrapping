<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Livewire\Vehiculos\EnvioJunkCar;
use App\Livewire\Vehiculos\ListaVehiculos;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El buscador de vehículos.
 *
 * Los tres primeros casos son errores reales medidos contra la base del
 * cliente: "toyota corolla" no encontraba nada teniendo el auto cargado,
 * el año no era buscable aunque la tarjeta lo muestra, y escribir un "%"
 * devolvía el inventario completo en vez de filtrar.
 */
class BuscadorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    private function vehiculo(string $marca, string $modelo, int $anio, ?EstadoVehiculo $estado = null): Vehicle
    {
        return Vehicle::factory()
            ->enEstado($estado ?? EstadoVehiculo::Comprado)
            ->create(['marca' => $marca, 'modelo' => $modelo, 'anio' => $anio]);
    }

    /** @return array<int, string> Nombres de los vehículos que devuelve la búsqueda. */
    private function buscar(string $termino): array
    {
        return Vehicle::query()->buscar($termino)->pluck('modelo')->all();
    }

    public function test_encuentra_con_dos_palabras_aunque_esten_en_columnas_distintas(): void
    {
        $this->vehiculo('Toyota', 'Corolla', 2014);
        $this->vehiculo('Honda', 'Civic', 2019);

        // Es la forma natural de buscar un auto, y era justo la que fallaba:
        // antes se comparaba la frase entera contra cada columna por separado.
        $this->assertSame(['Corolla'], $this->buscar('toyota corolla'));
        $this->assertSame(['Corolla'], $this->buscar('corolla toyota'));
        $this->assertSame([], $this->buscar('toyota civic'));
    }

    public function test_encuentra_por_anio(): void
    {
        $this->vehiculo('Honda', 'Civic', 2019);
        $this->vehiculo('Honda', 'CR-V', 2019);
        $this->vehiculo('Ford', 'Focus', 2006);

        // La tarjeta muestra "2019 Honda Civic": copiar ese texto tiene que
        // encontrar el auto.
        $this->assertCount(2, $this->buscar('2019'));
        $this->assertSame(['Civic'], $this->buscar('civic 2019'));
        $this->assertSame(['Civic'], $this->buscar('2019 honda civic'));
        $this->assertSame([], $this->buscar('2019 ford'));
    }

    public function test_los_comodines_no_devuelven_todo(): void
    {
        $this->vehiculo('Toyota', 'Corolla', 2014);
        $this->vehiculo('Honda', 'Civic', 2019);

        // Sin escapar, "%" y "_" son comodines de LIKE: un solo carácter
        // devolvía el inventario entero, o sea lo contrario de filtrar.
        $this->assertSame([], $this->buscar('%'));
        $this->assertSame([], $this->buscar('_'));
        $this->assertSame([], $this->buscar('%%'));
    }

    public function test_ignora_mayusculas_y_espacios_sobrantes(): void
    {
        $this->vehiculo('Toyota', 'Corolla', 2014);

        foreach (['TOYOTA', 'toyota', '  Toyota  ', 'ToYoTa   Corolla'] as $termino) {
            $this->assertCount(1, $this->buscar($termino), "Falló con «{$termino}»");
        }
    }

    public function test_un_termino_vacio_no_filtra_nada(): void
    {
        $this->vehiculo('Toyota', 'Corolla', 2014);
        $this->vehiculo('Honda', 'Civic', 2019);

        $this->assertCount(2, $this->buscar(''));
        $this->assertCount(2, $this->buscar('    '));
    }

    public function test_encuentra_por_vin_parcial(): void
    {
        $vehiculo = $this->vehiculo('Mazda', 'CX-5', 2018);
        $trozo = substr($vehiculo->vin, 4, 6);

        $this->assertSame(['CX-5'], $this->buscar($trozo));
        $this->assertSame(['CX-5'], $this->buscar(strtolower($trozo)));
    }

    public function test_la_busqueda_se_combina_con_el_filtro_de_estado(): void
    {
        $this->vehiculo('Honda', 'Civic', 2019, EstadoVehiculo::EnReparacion);
        $this->vehiculo('Honda', 'CR-V', 2019, EstadoVehiculo::Listo);

        $admin = User::factory()->create()->assignRole('admin');

        // Los dos filtros se combinan con Y: el OR entre columnas no puede
        // escaparse del grupo y traer vehículos de otros estados.
        Livewire::actingAs($admin)
            ->test(ListaVehiculos::class)
            ->set('busqueda', 'honda')
            ->assertSee('Civic')
            ->assertSee('CR-V')
            ->set('filtroEstado', EstadoVehiculo::Listo->value)
            ->assertSee('CR-V')
            ->assertDontSee('Civic');
    }

    public function test_al_buscar_se_vuelve_a_la_primera_pagina(): void
    {
        Vehicle::factory()->count(20)->create(['marca' => 'Nissan']);
        $this->vehiculo('Toyota', 'Corolla', 2014);

        $admin = User::factory()->create()->assignRole('admin');

        // Sin esto, buscar estando en la página 2 muestra "no hay resultados".
        Livewire::actingAs($admin)
            ->test(ListaVehiculos::class)
            ->call('gotoPage', 2)
            ->set('busqueda', 'corolla')
            ->assertSet('paginators.page', 1)
            ->assertSee('Corolla');
    }

    /**
     * Enviar a Junk car no se puede deshacer, y la selección sobrevive a los
     * cambios de búsqueda. Si el filtro esconde alguno de los seleccionados,
     * hay que avisarlo antes de que se vaya sin que nadie lo vea.
     */
    public function test_avisa_cuando_la_seleccion_queda_fuera_del_filtro(): void
    {
        $nissan = $this->vehiculo('Nissan', 'Sentra', 2011);
        $ford = $this->vehiculo('Ford', 'Focus', 2006);

        $componente = Livewire::actingAs(User::factory()->create()->assignRole('admin'))
            ->test(EnvioJunkCar::class)
            ->set('seleccion', [$nissan->id, $ford->id]);

        // Con los dos a la vista, no hay nada que advertir.
        $componente->assertViewHas('fueraDelFiltro', 0);

        // Al buscar "ford", el Nissan sigue seleccionado pero ya no se ve.
        $componente->set('buscar', 'ford')->assertViewHas('fueraDelFiltro', 1);

        // Y la selección no se pierde: sería tirar el trabajo del usuario.
        $componente->assertCount('seleccion', 2);
    }
}
