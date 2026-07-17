<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolAccesoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        return User::factory()->create()->assignRole($rol);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        $this->get('/usuarios')->assertRedirect('/login');
    }

    public function test_admin_puede_ver_gestion_de_usuarios(): void
    {
        $this->actingAs($this->usuarioConRol('admin'))
            ->get('/usuarios')
            ->assertOk();
    }

    public function test_roles_sin_permiso_no_ven_gestion_de_usuarios(): void
    {
        foreach (['comprador', 'mecanico', 'vendedor'] as $rol) {
            $this->actingAs($this->usuarioConRol($rol))
                ->get('/usuarios')
                ->assertForbidden();
        }
    }

    public function test_todo_usuario_autenticado_ve_el_panel(): void
    {
        $this->actingAs($this->usuarioConRol('mecanico'))
            ->get('/panel')
            ->assertOk();
    }
}
