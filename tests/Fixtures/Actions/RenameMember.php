<?php

namespace Overclock\LabKit\Tests\Fixtures\Actions;

use Illuminate\Database\Eloquent\Model;
use Overclock\LabKit\Contracts\ResourceAction;
use Overclock\LabKit\Operator;
use RuntimeException;

class RenameMember implements ResourceAction
{
    /** @var list<array{string, string}> */
    public static array $calls = [];

    public static bool $fails = false;

    public function handle(Model $record, Operator $operator): array
    {
        self::$calls[] = [(string) $record->getKey(), $operator->id];
        $record->forceFill(['name' => 'Renomeado'])->save();

        if (self::$fails) {
            throw new RuntimeException('database password secret-marker');
        }

        return ['renamed' => true];
    }
}
