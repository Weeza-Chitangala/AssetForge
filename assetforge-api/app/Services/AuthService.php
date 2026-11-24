<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        $token = $user->createToken('assetforge-token')->plainTextToken;

        activity('auth')
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'tenant_id' => $user->tenant_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        activity('auth')
            ->causedBy($user)
            ->event('logout')
            ->withProperties([
                'tenant_id' => $user->tenant_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged out');

        $user->currentAccessToken()?->delete();
    }
}