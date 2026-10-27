<?php

namespace App\LabKit;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Overclock\LabKit\Ability;
use Overclock\LabKit\Contracts\UserActions as UserActionsContract;
use Overclock\LabKit\Operator;

class UserActions implements UserActionsContract
{
    /** @var list<array{string, mixed, Operator}> */
    public static array $calls = [];

    /** @var list<Ability> */
    public static array $denied = [];

    public static bool $refuseActions = false;

    public function allows(Ability $ability, Operator $operator, ?Authenticatable $user = null): bool
    {
        return ! in_array($ability, self::$denied, true);
    }

    public function block(Authenticatable $user, Operator $operator): void
    {
        if (self::$refuseActions) {
            throw new AuthorizationException('Policy denied for user secret-marker.');
        }

        self::$calls[] = ['block', $user->getAuthIdentifier(), $operator];
    }

    public function unblock(Authenticatable $user, Operator $operator): void
    {
        self::$calls[] = ['unblock', $user->getAuthIdentifier(), $operator];
    }

    public function forcePasswordReset(Authenticatable $user, Operator $operator): void
    {
        self::$calls[] = ['forcePasswordReset', $user->getAuthIdentifier(), $operator];
    }
}
