<?php

namespace Overclock\LabKit\Tests\Fixtures\Actions;

use Illuminate\Database\Eloquent\Model;
use Overclock\LabKit\Contracts\ResourceAction;
use Overclock\LabKit\Operator;

class TouchNothing implements ResourceAction
{
    public function handle(Model $record, Operator $operator): array
    {
        return ['done' => true];
    }
}
