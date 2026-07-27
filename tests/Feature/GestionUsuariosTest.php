<?php

namespace Tests\Feature;

use App\Livewire\Admin\GestionUsuarios;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Invariantes de la gestión de usuarios: el sistema nunca puede quedarse
 * sin ningún administrador, y nadie se cambia el rol a sí mismo.
 */
class GestionUsuariosTest extends TestCase
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

    public function test_no_se_puede_eliminar_al_ultimo_admin(): void
    {
        $admin = $this->usuarioConRol('admin');
        $otroAdmin = $this->usuarioConRol('admin');

        // Con dos admins, borrar uno está permitido.
        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('eliminar', $otroAdmin->id);

        $this->assertDatabaseMissing('users', ['id' => $otroAdmin->id]);

        // Ahora queda uno solo: un segundo admin creado para intentar borrarlo.
        $ultimo = $this->usuarioConRol('admin');
        $mecanico = $this->usuarioConRol('mecanico');
        $admin->syncRoles(['mecanico']); // deja a $ultimo como único admin
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Livewire::actingAs($ultimo)
            ->test(GestionUsuarios::class)
            ->call('eliminar', $mecanico->id); // borra a otro: permitido

        $this->assertDatabaseMissing('users', ['id' => $mecanico->id]);

        // Pero el último admin sobrevive aunque otro intente borrarlo.
        $nuevoAdmin = $this->usuarioConRol('admin');
        $ultimo->syncRoles(['vendedor']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Livewire::actingAs($nuevoAdmin)
            ->test(GestionUsuarios::class)
            ->call('eliminar', $nuevoAdmin->id); // es él mismo: bloqueado

        $this->assertDatabaseHas('users', ['id' => $nuevoAdmin->id]);
        $this->assertSame(1, User::role('admin')->count());
    }

    public function test_un_admin_no_puede_cambiar_su_propio_rol(): void
    {
        $admin = $this->usuarioConRol('admin');

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('editar', $admin->id)
            ->set('rol', 'mecanico')
            ->call('guardar');

        $admin->refresh();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('mecanico'));
    }

    public function test_no_se_puede_degradar_al_ultimo_admin(): void
    {
        $admin = $this->usuarioConRol('admin');   // el que opera
        $otro = $this->usuarioConRol('admin');    // el que se intenta degradar

        // Con dos admins, degradar a uno es válido.
        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('editar', $otro->id)
            ->set('rol', 'vendedor')
            ->call('guardar');

        $otro->refresh();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue($otro->hasRole('vendedor'));

        // Ahora $admin es el único: otro usuario no puede degradarlo.
        $vendedor = $this->usuarioConRol('vendedor');
        $this->assertSame(1, User::role('admin')->count());

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('editar', $admin->id)
            ->set('rol', 'vendedor')
            ->call('guardar');

        $admin->refresh();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertSame(1, User::role('admin')->count());
    }

    public function test_los_datos_se_guardan_si_el_rol_es_valido(): void
    {
        $admin = $this->usuarioConRol('admin');
        $this->usuarioConRol('admin'); // para que no sea el último

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('crear')
            ->set('name', 'Nuevo Gruero')
            ->set('email', 'nuevo@fortetowing.com')
            ->set('password', 'password123')
            ->set('rol', 'gruero')
            ->call('guardar')
            ->assertHasNoErrors();

        $creado = User::where('email', 'nuevo@fortetowing.com')->sole();

        $this->assertSame('Nuevo Gruero', $creado->name);
        $this->assertTrue($creado->hasRole('gruero'));
    }

    public function test_las_acciones_revalidan_el_permiso(): void
    {
        $admin = $this->usuarioConRol('admin');
        $this->usuarioConRol('admin');
        $otro = $this->usuarioConRol('mecanico');

        $componente = Livewire::actingAs($admin)->test(GestionUsuarios::class);

        // Le quitan el rol mientras tiene la pantalla abierta.
        $admin->syncRoles(['vendedor']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $componente->call('eliminar', $otro->id)->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otro->id]);
    }
}
