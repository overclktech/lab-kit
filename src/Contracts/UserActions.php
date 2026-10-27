<?php

namespace Overclock\LabKit\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Overclock\LabKit\Operator;

interface UserActions
{
    public function block(Authenticatable $user, Operator $operator): void;

    public function unblock(Authenticatable $user, Operator $operator): void;

    public function forcePasswordReset(Authenticatable $user, Operator $operator): void;
}
