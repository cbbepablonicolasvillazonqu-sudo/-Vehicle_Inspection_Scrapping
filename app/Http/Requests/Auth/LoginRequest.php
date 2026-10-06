<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Segundo límite, por correo y sin importar la IP.
     *
     * El de Breeze cuenta por correo + IP, y la IP sale de X-Forwarded-For
     * porque se confía en el proxy (CDN de Hostinger, túnel de Cloudflare).
     * Quien llegue al servidor sin pasar por ellos puede inventar una IP
     * distinta en cada intento y ese contador nunca se llena. Este sí.
     */
    private const INTENTOS_POR_CORREO = 10;

    private const BLOQUEO_POR_CORREO_SEGUNDOS = 15 * 60;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->throttleKeyCorreo(), self::BLOQUEO_POR_CORREO_SEGUNDOS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyCorreo());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $clave = match (true) {
            RateLimiter::tooManyAttempts($this->throttleKey(), 5) => $this->throttleKey(),
            RateLimiter::tooManyAttempts($this->throttleKeyCorreo(), self::INTENTOS_POR_CORREO) => $this->throttleKeyCorreo(),
            default => null,
        };

        if ($clave === null) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($clave);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /** Clave del límite por correo, sin la IP. */
    private function throttleKeyCorreo(): string
    {
        return 'login-correo|'.Str::transliterate(Str::lower($this->string('email')));
    }
}
