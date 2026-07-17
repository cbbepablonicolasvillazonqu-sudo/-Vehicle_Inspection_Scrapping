<?php

namespace Tests\Feature;

use App\Models\User;
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
}
