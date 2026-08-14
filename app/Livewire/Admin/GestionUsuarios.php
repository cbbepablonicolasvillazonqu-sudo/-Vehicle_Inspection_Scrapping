<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * CRUD de usuarios y asignación de roles. Solo accesible para Admin
 * (protegido por middleware "role:admin" en la ruta y verificado en mount()).
 */
#[Layout('layouts.app')]
#[Title('Usuarios')]
class GestionUsuarios extends Component
{
    public bool $mostrandoFormulario = false;

    public ?int $usuarioId = null;

    public string $name = '';

    public string $email = '';

    /** Teléfono de contacto (opcional): solo lo carga el Admin. */
    public string $telefono = '';

    public string $password = '';

    public string $rol = '';

    public function mount(): void
    {
        abort_unless($this->puedeGestionar(), 403);
    }

    public function puedeGestionar(): bool
    {
        return auth()->user()->can('gestionar usuarios');
    }

    /** ¿Es el único administrador que queda? Si lo es, no se toca. */
    private function esUltimoAdmin(User $usuario): bool
    {
        return $usuario->hasRole('admin') && User::role('admin')->count() <= 1;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($this->usuarioId),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => $this->usuarioId
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::exists('roles', 'name')],
        ];
    }

    public function crear(): void
    {
        abort_unless($this->puedeGestionar(), 403);

        $this->resetValidation();
        $this->reset(['usuarioId', 'name', 'email', 'telefono', 'password', 'rol']);
        $this->mostrandoFormulario = true;
    }

    public function editar(int $id): void
    {
        abort_unless($this->puedeGestionar(), 403);

        $usuario = User::findOrFail($id);

        $this->resetValidation();
        $this->usuarioId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->telefono = (string) $usuario->telefono;
        $this->password = '';
        $this->rol = $usuario->getRoleNames()->first() ?? '';
        $this->mostrandoFormulario = true;
    }

    public function guardar(): void
    {
        abort_unless($this->puedeGestionar(), 403);

        $datos = $this->validate();

        // Invariantes que se validan ANTES de guardar nada, para no dejar el
        // usuario a medio actualizar si el cambio de rol no está permitido.
        if ($this->usuarioId) {
            $editado = User::findOrFail($this->usuarioId);
            $rolActual = $editado->getRoleNames()->first();

            // Nadie se cambia su propio rol: evita que un admin se degrade solo.
            if ($editado->id === auth()->id() && $datos['rol'] !== $rolActual) {
                $this->dispatch('notificar', mensaje: __('No puedes cambiar tu propio rol'));

                return;
            }

            // Y el último admin no puede perder el rol: dejaría el sistema sin nadie
            // que gestione usuarios ni vea las ganancias.
            if ($datos['rol'] !== 'admin' && $this->esUltimoAdmin($editado)) {
                $this->dispatch('notificar', mensaje: __('No puedes quitarle el rol al último administrador'));

                return;
            }
        }

        // El teléfono es opcional: vacío se guarda como NULL, no como cadena vacía.
        $telefono = filled($datos['telefono'] ?? null) ? $datos['telefono'] : null;

        if ($this->usuarioId) {
            $usuario = User::findOrFail($this->usuarioId);
            $usuario->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $telefono,
            ]);

            if (filled($datos['password'])) {
                $usuario->update(['password' => Hash::make($datos['password'])]);
            }
        } else {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'telefono' => $telefono,
                'password' => Hash::make($datos['password']),
                'email_verified_at' => now(),
            ]);
        }

        $usuario->syncRoles([$datos['rol']]);

        $this->mostrandoFormulario = false;
        $this->dispatch('notificar', mensaje: $this->usuarioId ? __('Usuario actualizado') : __('Usuario creado'));
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
    }

    public function eliminar(int $id): void
    {
        abort_unless($this->puedeGestionar(), 403);

        if ($id === auth()->id()) {
            $this->dispatch('notificar', mensaje: __('No puedes eliminar tu propia cuenta'));

            return;
        }

        $usuario = User::findOrFail($id);

        // Sin admins nadie podría gestionar usuarios ni ver las ganancias, y no
        // hay forma de recuperarlo desde la interfaz.
        if ($this->esUltimoAdmin($usuario)) {
            $this->dispatch('notificar', mensaje: __('No puedes eliminar al último administrador'));

            return;
        }

        $usuario->delete();
        $this->dispatch('notificar', mensaje: __('Usuario eliminado'));
    }

    public function render()
    {
        return view('livewire.admin.gestion-usuarios', [
            'usuarios' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
