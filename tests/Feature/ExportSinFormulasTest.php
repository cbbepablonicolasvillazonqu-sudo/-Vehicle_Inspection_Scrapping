<?php

namespace Tests\Feature;

use App\Enums\EstadoVehiculo;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

/**
 * Un texto de la base nunca se ejecuta como fórmula al abrir la exportación.
 *
 * Antes una nota "=1+1" llegaba al XLSX como fórmula, y en el CSV Excel
 * interpreta igual lo que empieza con "+", "-" o "@". Los números tienen que
 * seguir siendo números: si no, las sumas del Excel dejan de funcionar.
 */
class ExportSinFormulasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');

        // Vendido a pérdida: la ganancia es un número negativo, que no se toca.
        $vehiculo = Vehicle::factory()->enEstado(EstadoVehiculo::Vendido)->create([
            'marca' => '@SUMA(A1)',
            'modelo' => '+Civic',
            'notas' => '=1+1',
            'precio_compra' => 2000,
        ]);
        $vehiculo->venta()->create([
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_venta' => 1500,
            'nombre_comprador' => 'Cliente',
            'metodo_pago' => 'zelle',
        ]);
    }

    /** Fila de datos indexada por el encabezado de cada columna. */
    private function filaXlsx(Worksheet $hoja): array
    {
        $fila = [];

        foreach ($hoja->getRowIterator(1, 1)->current()->getCellIterator() as $celda) {
            $fila[$celda->getValue()] = $hoja->getCell($celda->getColumn().'2');
        }

        return $fila;
    }

    public function test_en_xlsx_los_textos_quedan_como_texto_y_los_numeros_como_numeros(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/exportar/vehiculos/xlsx')->assertOk();
        $fila = $this->filaXlsx(IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getActiveSheet());

        foreach (['Notas' => '=1+1', 'Marca' => '@SUMA(A1)', 'Modelo' => '+Civic'] as $columna => $texto) {
            $this->assertSame(DataType::TYPE_STRING, $fila[$columna]->getDataType(), $columna);
            $this->assertSame($texto, $fila[$columna]->getValue(), "{$columna}: sin apóstrofo, tal cual");
        }

        $this->assertSame(DataType::TYPE_NUMERIC, $fila['Precio de compra']->getDataType());
        $this->assertEquals(2000, $fila['Precio de compra']->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $fila['Ganancia']->getDataType());
        $this->assertEquals(-500, $fila['Ganancia']->getValue());
    }

    public function test_en_csv_los_textos_peligrosos_llevan_un_apostrofo_y_los_numeros_no(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/exportar/vehiculos/csv')->assertOk();

        $lineas = array_map('str_getcsv', preg_split('/\R/', trim($respuesta->baseResponse->getFile()->getContent())));
        $fila = array_combine($lineas[0], $lineas[1]);

        $this->assertSame("'=1+1", $fila['Notas']);
        $this->assertSame("'@SUMA(A1)", $fila['Marca']);
        $this->assertSame("'+Civic", $fila['Modelo']);
        $this->assertSame('2000', $fila['Precio de compra']);
        $this->assertSame('-500', $fila['Ganancia']);
    }

    public function test_el_reporte_de_ganancias_sigue_con_sus_numeros(): void
    {
        $respuesta = $this->actingAs($this->admin)->get('/exportar/ganancias')->assertOk();
        $fila = $this->filaXlsx(IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getActiveSheet());

        $this->assertSame(DataType::TYPE_NUMERIC, $fila['Ganancia']->getDataType());
        $this->assertEquals(-500, $fila['Ganancia']->getValue());
        $this->assertSame(DataType::TYPE_STRING, $fila['Vehículo']->getDataType());
    }
}
