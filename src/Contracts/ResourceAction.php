<?php

namespace Overclock\LabKit\Contracts;

use Illuminate\Database\Eloquent\Model;
use Overclock\LabKit\Operator;

interface ResourceAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Model $record, Operator $operator): array;
}
