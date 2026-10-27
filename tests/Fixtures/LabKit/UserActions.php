<?php

namespace App\LabKit;

use Illuminate\Contracts\Auth\Authenticatable;
use Overclock\LabKit\Contracts\UserActions as UserActionsContract;
use Overclock\LabKit\Operator;

class UserActions implements UserActionsContract
{
    /** @var list<array{string, mixed, Operator}> */
    public static array $calls = [];

    public function block(Authenticatable $user, Operator $operator): void
    {
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
