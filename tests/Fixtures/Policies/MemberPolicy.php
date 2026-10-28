<?php

namespace Overclock\LabKit\Tests\Fixtures\Policies;

use Overclock\LabKit\Operator;

class MemberPolicy
{
    public static bool $allows = true;

    public function labViewAny(Operator $operator): bool
    {
        return self::$allows;
    }
}
