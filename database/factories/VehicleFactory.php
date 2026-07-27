<?php

namespace Database\Factories;

use App\Enums\EstadoTitulo;
use App\Enums\EstadoVehiculo;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $marcas = [
            'Toyota' => ['Corolla', 'Camry', 'RAV4', 'Tacoma'],
            'Honda' => ['Civic', 'Accord', 'CR-V'],
            'Ford' => ['F-150', 'Focus', 'Escape'],
            'Chevrolet' => ['Silverado', 'Malibu', 'Equinox'],
            'Nissan' => ['Altima', 'Sentra', 'Rogue'],
        ];

        $marca = $this->faker->randomElement(array_keys($marcas));

        return [
            'marca' => $marca,
            'modelo' => $this->faker->randomElement($marcas[$marca]),
            'anio' => $this->faker->numberBetween(2005, 2022),
            'vin' => self::vinAleatorio(),
            'millas' => $this->faker->numberBetween(30_000, 220_000),
            'precio_compra' => $this->faker->randomFloat(2, 500, 9500),
            'fecha_compra' => $this->faker->dateTimeBetween('-10 months', 'now')->format('Y-m-d'),
            'estado_titulo' => $this->faker->randomElement(EstadoTitulo::cases()),
            'estado' => EstadoVehiculo::Comprado,
            'notas' => $this->faker->boolean(40) ? $this->faker->sentence(8) : null,
        ];
    }

    /** VIN válido de 17 caracteres (sin I, O ni Q). */
    public static function vinAleatorio(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = '';

        for ($i = 0; $i < 17; $i++) {
            $vin .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $vin;
    }

    public function enEstado(EstadoVehiculo $estado): static
    {
        return $this->state(fn () => ['estado' => $estado]);
    }
}
