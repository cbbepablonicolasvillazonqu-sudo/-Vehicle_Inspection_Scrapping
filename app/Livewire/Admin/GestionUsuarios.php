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

    public string $password = '';

    public string $rol = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('gestionar usuarios'), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($this->usuarioId),
            ],
            'password' => $this->usuarioId
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::exists('roles', 'name')],
        ];
    }

    public function crear(): void
    {
        $this->resetValidation();
        $this->reset(['usuarioId', 'name', 'email', 'password', 'rol']);
        $this->mostrandoFormulario = true;
    }

    public function editar(int $id): void
    {
        $usuario = User::findOrFail($id);

        $this->resetValidation();
        $this->usuarioId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->password = '';
        $this->rol = $usuario->getRoleNames()->first() ?? '';
        $this->mostrandoFormulario = true;
    }

    public function guardar(): void
    {
        $datos = $this->validate();

        if ($this->usuarioId) {
            $usuario = User::findOrFail($this->usuarioId);
            $usuario->update([
                'name' => $datos['name'],
                'email' => $datos['email'],
            ]);

            if (filled($datos['password'])) {
                $usuario->update(['password' => Hash::make($datos['password'])]);
            }
        } else {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'email_verified_at' => now(),
            ]);
        }

        $usuario->syncRoles([$datos['rol']]);

        $this->mostrandoFormulario = false;
        $this->dispatch('notificar', mensaje: $this->usuarioId ? 'Usuario actualizado' : 'Usuario creado');
    }

    public function cancelar(): void
    {
        $this->mostrandoFormulario = false;
    }

    public function eliminar(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notificar', mensaje: 'No puedes eliminar tu propia cuenta');

            return;
        }

        User::findOrFail($id)->delete();
        $this->dispatch('notificar', mensaje: 'Usuario eliminado');
    }

    public function render()
    {
        return view('livewire.admin.gestion-usuarios', [
            'usuarios' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }
}
