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

    /* ------------------------ Teléfono de contacto ------------------------ */

    public function test_el_admin_crea_un_usuario_con_telefono(): void
    {
        $admin = $this->usuarioConRol('admin');
        $this->usuarioConRol('admin'); // para que no sea el último

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('crear')
            ->set('name', 'Juan Gruero')
            ->set('email', 'juan@fortetowing.com')
            ->set('telefono', '(555) 123-4567')
            ->set('password', 'password123')
            ->set('rol', 'gruero')
            ->call('guardar')
            ->assertHasNoErrors();

        $creado = User::where('email', 'juan@fortetowing.com')->sole();

        $this->assertSame('(555) 123-4567', $creado->telefono);
        $this->assertTrue($creado->hasRole('gruero'));
    }

    public function test_el_telefono_es_opcional_y_vacio_se_guarda_como_null(): void
    {
        $admin = $this->usuarioConRol('admin');
        $this->usuarioConRol('admin');

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('crear')
            ->set('name', 'Sin Telefono')
            ->set('email', 'sintel@fortetowing.com')
            ->set('password', 'password123')
            ->set('rol', 'mecanico')
            ->call('guardar')
            ->assertHasNoErrors();

        // Null, no cadena vacía: así la vista no muestra un enlace tel: vacío.
        $this->assertNull(User::where('email', 'sintel@fortetowing.com')->sole()->telefono);
    }

    public function test_al_editar_se_precarga_el_telefono_y_se_actualiza(): void
    {
        $admin = $this->usuarioConRol('admin');
        $this->usuarioConRol('admin');

        $gruero = $this->usuarioConRol('gruero');
        $gruero->update(['telefono' => '555-0000']);

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->call('editar', $gruero->id)
            ->assertSet('telefono', '555-0000')
            ->set('telefono', '555-9999')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('555-9999', $gruero->fresh()->telefono);
    }

    public function test_la_lista_muestra_el_telefono_del_usuario(): void
    {
        $admin = $this->usuarioConRol('admin');
        $gruero = $this->usuarioConRol('gruero');
        $gruero->update(['telefono' => '555-4321']);

        Livewire::actingAs($admin)
            ->test(GestionUsuarios::class)
            ->assertSee('555-4321');
    }
}
