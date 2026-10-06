<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Límite de intentos del login.
 *
 * El de Breeze cuenta por correo + IP, y la IP sale de X-Forwarded-For porque
 * se confía en el proxy. Quien llegue al servidor sin pasar por el CDN puede
 * mandar una IP distinta en cada intento y ese contador nunca se llena. Hay un
 * segundo límite por correo, sin la IP, que sí se llena.
 */
class LimiteLoginTest extends TestCase
{
    use RefreshDatabase;

    private function intento(User $usuario, string $password, string $ip = '198.51.100.7')
    {
        return $this->withHeader('X-Forwarded-For', $ip)->post('/login', [
            'email' => $usuario->email,
            'password' => $password,
        ]);
    }

    public function test_cinco_intentos_desde_la_misma_ip_bloquean_como_siempre(): void
    {
        $usuario = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->intento($usuario, 'incorrecta');
        }

        // Con la contraseña correcta: si no estuviera bloqueado, entraría.
        $this->intento($usuario, 'password')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cambiar_la_ip_en_cada_intento_no_evita_el_bloqueo(): void
    {
        $usuario = User::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->intento($usuario, 'incorrecta', "203.0.113.{$i}");
        }

        $this->intento($usuario, 'password', '203.0.113.99')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_bloqueo_de_un_correo_no_afecta_a_otro(): void
    {
        $atacado = User::factory()->create();
        $otro = User::factory()->create();

        for ($i = 1; $i <= 10; $i++) {
            $this->intento($atacado, 'incorrecta', "203.0.113.{$i}");
        }

        $this->intento($otro, 'password', '203.0.113.50');
        $this->assertAuthenticatedAs($otro);
    }

    public function test_entrar_bien_reinicia_la_cuenta(): void
    {
        $usuario = User::factory()->create();

        for ($i = 1; $i <= 9; $i++) {
            $this->intento($usuario, 'incorrecta', "203.0.113.{$i}");
        }

        $this->intento($usuario, 'password', '203.0.113.20');
        $this->assertAuthenticatedAs($usuario);
        $this->post('/logout');

        // Si la cuenta no se hubiera reiniciado, con estos 9 serían 18.
        for ($i = 21; $i <= 29; $i++) {
            $this->intento($usuario, 'incorrecta', "203.0.113.{$i}");
        }

        $this->intento($usuario, 'password', '203.0.113.30');
        $this->assertAuthenticatedAs($usuario);
    }
}
