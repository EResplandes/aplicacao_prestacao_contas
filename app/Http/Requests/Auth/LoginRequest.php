<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\User;
use App\Services\SecurityEventService;
use App\Support\AdminPanel;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = Str::lower($this->string('email')->value());
        $user = User::query()->where('email', $email)->first();

        if ($user && ! $user->is_active) {
            app(SecurityEventService::class)->recordFailedLogin(
                channel: 'web',
                identifier: $email,
                user: $user,
                reason: 'inactive_account',
            );

            throw ValidationException::withMessages([
                'email' => 'Sua conta está inativa. Procure o administrador do sistema.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $email,
            'password' => $this->string('password')->value(),
        ], $this->boolean('remember'))) {
            foreach ($this->throttleKeys() as $key) {
                RateLimiter::hit($key, 900);
            }

            app(SecurityEventService::class)->recordFailedLogin(
                channel: 'web',
                identifier: $email,
                user: $user,
            );

            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas não são corretas.',
            ]);
        }

        $authenticatedUser = Auth::user();

        if (! AdminPanel::canAccess($authenticatedUser)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => AdminPanel::isRequester($authenticatedUser)
                    ? 'O perfil solicitante acessa somente o aplicativo mobile.'
                    : 'Este perfil não possui acesso ao painel administrativo.',
            ]);
        }

        foreach ($this->throttleKeys() as $key) {
            RateLimiter::clear($key);
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        $tooManyAttempts = collect($this->throttleKeys())
            ->contains(fn (string $key): bool => RateLimiter::tooManyAttempts($key, 5));

        if (! $tooManyAttempts) {
            return;
        }

        event(new Lockout($this));

        $seconds = (int) collect($this->throttleKeys())
            ->map(fn (string $key): int => RateLimiter::availableIn($key))
            ->max();

        app(SecurityEventService::class)->recordLockout(
            channel: 'web',
            identifier: Str::lower($this->string('email')->value()),
            retryAfterSeconds: $seconds,
        );

        throw ValidationException::withMessages([
            'email' => "Muitas tentativas de acesso. Tente novamente em {$seconds} segundos.",
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function throttleKeys(): array
    {
        $email = Str::transliterate(Str::lower($this->string('email')->value()).'|'.$this->ip());

        return [
            'web-login:credential:'.$email,
            'web-login:ip:'.$this->ip(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'email' => SanitizesInput::normalizeEmail($this->input('email')),
        ]));
    }
}
