<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea la cuenta de administrador en producción.
 *
 * Existe para no depender de UsuariosSeeder, que usa updateOrCreate y por lo
 * tanto reescribe las contraseñas de las 4 cuentas cada vez que corre.
 */
class CrearAdmin extends Command
{
    protected $signature = 'forte:crear-admin
        {email : Correo de la cuenta}
        {--nombre=Administrador : Nombre a mostrar}
        {--telefono= : Teléfono de contacto (opcional)}
        {--hash= : Hash bcrypt ya existente (para migrar la cuenta desde otra instalación)}
        {--password= : Contraseña en claro, que se hashea aquí}
        {--forzar : Reemplazar la contraseña de un usuario que ya existe}';

    protected $description = 'Crea (o repara) la cuenta de administrador sin tocar las demás';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $hash = $this->option('hash');
        $password = $this->option('password');

        if (($hash === null) === ($password === null)) {
            $this->error('Indicá exactamente uno: --hash o --password.');

            return self::FAILURE;
        }

        // El rol tiene que existir antes: si no, la cuenta quedaría sin permisos.
        if (! Role::where('name', 'admin')->where('guard_name', 'web')->exists()) {
            $this->error('No existe el rol "admin". Ejecutá antes: php artisan db:seed --class=ProduccionSeeder --force');

            return self::FAILURE;
        }

        if ($hash !== null) {
            if (! Hash::isHashed($hash) || (password_get_info($hash)['algoName'] ?? null) !== 'bcrypt') {
                $this->error('El valor de --hash no parece un hash bcrypt válido.');

                return self::FAILURE;
            }

            $coste = password_get_info($hash)['options']['cost'] ?? null;
            $configurado = config('hashing.bcrypt.rounds', 12);

            if ($coste !== null && $coste > $configurado) {
                $this->warn("El hash tiene coste {$coste} y BCRYPT_ROUNDS vale {$configurado}.");
                $this->warn('Subí BCRYPT_ROUNDS a '.$coste.' o mayor, o Laravel rechazará la contraseña al cambiarla.');
            }
        } else {
            $hash = Hash::make($password);
        }

        $existente = User::where('email', $email)->first();

        if ($existente && ! $this->option('forzar')) {
            $this->error("Ya existe un usuario con {$email}.");
            $this->line('Usá --forzar solo si querés reemplazar su contraseña.');

            return self::FAILURE;
        }

        // Se escribe con query builder a propósito: el modelo tiene el cast
        // "hashed", que rechaza un hash cuyo coste supere BCRYPT_ROUNDS.
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            array_filter([
                'name' => $this->option('nombre'),
                'telefono' => $this->option('telefono') ?: null,
                'password' => $hash,
                'locale' => 'es',
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => $existente?->created_at ?? now(),
            ], fn ($v) => $v !== null),
        );

        User::where('email', $email)->firstOrFail()->syncRoles(['admin']);

        // Spatie cachea permisos 24 h: sin esto el rol nuevo no se ve.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(($existente ? 'Administrador actualizado: ' : 'Administrador creado: ').$email);
        $this->line('Entrá con la contraseña que ya usabas y verificá el acceso.');

        return self::SUCCESS;
    }
}
