<?php

namespace Database\Seeders;

use App\Enums\CategoriaGasto;
use App\Enums\EstadoVehiculo;
use App\Enums\MetodoPago;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ServicioAuditoria;
use Illuminate\Database\Seeder;

/**
 * Vehículos de demostración para presentar la app al cliente.
 * Solo se ejecuta cuando SEED_DEMO_DATA=true (entorno local).
 */
class DemoVehiculosSeeder extends Seeder
{
    public function run(): void
    {
        if (Vehicle::query()->exists()) {
            $this->command?->warn('Ya existen vehículos; se omite el seeder de demostración.');

            return;
        }

        $comprador = User::where('email', 'compras@fortetowing.com')->first() ?? User::first();
        $mecanico = User::where('email', 'taller@fortetowing.com')->first() ?? $comprador;
        $vendedor = User::where('email', 'ventas@fortetowing.com')->first() ?? $comprador;
        $admin = User::where('email', 'admin@fortetowing.com')->first() ?? $comprador;

        $auditoria = app(ServicioAuditoria::class);

        $plan = [
            // [estado final, gastos, venta/desguace]
            ['estado' => EstadoVehiculo::Comprado],
            ['estado' => EstadoVehiculo::Comprado],
            ['estado' => EstadoVehiculo::EnReparacion, 'gastos' => [['reparacion', 'Cambio de frenos', 180], ['piezas', 'Batería nueva', 145]]],
            ['estado' => EstadoVehiculo::EnReparacion, 'gastos' => [['grua_transporte', 'Grúa desde subasta', 120]]],
            ['estado' => EstadoVehiculo::Listo, 'gastos' => [['reparacion', 'Suspensión delantera', 310], ['titulo_tramites', 'Trámite de título', 75]]],
            ['estado' => EstadoVehiculo::Listo, 'gastos' => [['piezas', 'Llantas usadas (4)', 260]]],
            ['estado' => EstadoVehiculo::Publicado, 'gastos' => [['reparacion', 'Afinación general', 220]]],
            ['estado' => EstadoVehiculo::Vendido, 'gastos' => [['reparacion', 'Radiador', 240], ['piezas', 'Faros', 90]], 'venta' => 2600],
            ['estado' => EstadoVehiculo::Vendido, 'gastos' => [['reparacion', 'Transmisión (sello)', 380]], 'venta' => 3900],
            ['estado' => EstadoVehiculo::Desguace, 'gastos' => [['grua_transporte', 'Traslado al lote', 90]], 'desguace' => 420],
        ];

        // Cadena de estados hasta llegar al estado final del plan.
        $cadena = [
            EstadoVehiculo::Comprado->value => [],
            EstadoVehiculo::EnReparacion->value => [EstadoVehiculo::EnReparacion],
            EstadoVehiculo::Listo->value => [EstadoVehiculo::EnReparacion, EstadoVehiculo::Listo],
            EstadoVehiculo::Publicado->value => [EstadoVehiculo::EnReparacion, EstadoVehiculo::Listo, EstadoVehiculo::Publicado],
            EstadoVehiculo::Vendido->value => [EstadoVehiculo::EnReparacion, EstadoVehiculo::Listo, EstadoVehiculo::Publicado, EstadoVehiculo::Vendido],
            EstadoVehiculo::Desguace->value => [EstadoVehiculo::Desguace],
        ];

        // Quién suele hacer cada transición.
        $responsable = fn (EstadoVehiculo $estado) => match ($estado) {
            EstadoVehiculo::EnReparacion => $comprador,
            EstadoVehiculo::Listo => $mecanico,
            EstadoVehiculo::Publicado, EstadoVehiculo::Vendido => $vendedor,
            EstadoVehiculo::Desguace => $admin,
            default => $comprador,
        };

        foreach ($plan as $indice => $config) {
            $vehiculo = Vehicle::factory()->create([
                'created_by' => $comprador->id,
                'fecha_compra' => now()->subDays(90 - $indice * 8)->format('Y-m-d'),
            ]);

            // Historial inicial.
            $fecha = $vehiculo->fecha_compra->copy()->setTime(9, 0);
            $vehiculo->historialEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => EstadoVehiculo::Comprado->value,
                'user_id' => $comprador->id,
                'nota' => 'Registro inicial',
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
            $auditoria->registrar($vehiculo, 'vehiculo_creado', ['vin' => $vehiculo->vin], $comprador);

            // Transiciones intermedias.
            $anterior = EstadoVehiculo::Comprado;

            foreach ($cadena[$config['estado']->value] as $paso => $estado) {
                $fecha = $fecha->copy()->addDays(4 + $paso * 5);
                $usuario = $responsable($estado);

                $vehiculo->historialEstados()->create([
                    'estado_anterior' => $anterior->value,
                    'estado_nuevo' => $estado->value,
                    'user_id' => $usuario->id,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);

                $anterior = $estado;
            }

            $vehiculo->forceFill(['estado' => $config['estado']])->save();

            // Gastos.
            foreach ($config['gastos'] ?? [] as [$categoria, $descripcion, $monto]) {
                $vehiculo->gastos()->create([
                    'categoria' => CategoriaGasto::from($categoria),
                    'descripcion' => $descripcion,
                    'monto' => $monto,
                    'fecha' => $vehiculo->fecha_compra->copy()->addDays(7)->format('Y-m-d'),
                    'user_id' => $mecanico->id,
                ]);
            }

            // Venta (algunas de este mes para que el panel muestre datos).
            if (isset($config['venta'])) {
                $fechaVenta = $indice % 2 === 0 ? now()->subDays(5) : now()->subDays(20);

                $vehiculo->venta()->create([
                    'fecha_venta' => $fechaVenta->format('Y-m-d'),
                    'precio_venta' => $config['venta'],
                    'nombre_comprador' => fake('es_ES')->name(),
                    'telefono_comprador' => fake()->numerify('(###) ###-####'),
                    'metodo_pago' => fake()->randomElement(MetodoPago::cases()),
                    'user_id' => $vendedor->id,
                ]);

                $auditoria->registrar($vehiculo, 'venta_registrada', ['precio' => $config['venta']], $vendedor);
            }

            // Desguace.
            if (isset($config['desguace'])) {
                $vehiculo->desguace()->create([
                    'fecha' => now()->subDays(12)->format('Y-m-d'),
                    'monto_recibido' => $config['desguace'],
                    'empresa' => 'Junkyard Central',
                    'user_id' => $admin->id,
                ]);

                $auditoria->registrar($vehiculo, 'desguace_registrado', ['monto' => $config['desguace']], $admin);
            }
        }

        $this->command?->info('Vehículos de demostración creados: '.Vehicle::count());
    }
}
