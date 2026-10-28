<?php

namespace Overclock\LabKit\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;
use Overclock\LabKit\Operator;

class MemberPolicy
{
    public static bool $allows = true;

    public static bool $runs = true;

    public function labViewAny(Operator $operator): bool
    {
        return self::$allows;
    }

    public function labRun(Operator $operator, Model $record, string $action): bool
    {
        return self::$runs;
    }
}
