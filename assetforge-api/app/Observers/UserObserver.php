<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuthService;

/**
 * UserObserver
 *
 * Centralises automatic Sanctum token revocation whenever a user's password
 * is changed, their account is deactivated/suspended, or the account is deleted—
 * regardless of whether the mutation originates from an API controller, CLI, or service.
 */
class UserObserver
{
    public function __construct(
        private readonly AuthService $authService,
    ) {
    }

    public function updated(User $user): void
    {
        $causer = request()?->user() ?? auth('sanctum')->user() ?? auth()->user();

        if ($user->status !== 'active' && $user->wasChanged('status')) {
            $this->authService->revokeUserTokens(
                $user,
                'account_' . ($user->status ?: 'inactive'),
                $causer instanceof User ? $causer : null
            );

            return;
        }

        if ($user->wasChanged('password')) {
            $this->authService->revokeUserTokens(
                $user,
                'password_changed',
                $causer instanceof User ? $causer : null
            );
        }
    }

    public function deleting(User $user): void
    {
        $causer = request()?->user() ?? auth('sanctum')->user() ?? auth()->user();

        $this->authService->revokeUserTokens(
            $user,
            'account_deleted',
            $causer instanceof User ? $causer : null
        );
    }
}
