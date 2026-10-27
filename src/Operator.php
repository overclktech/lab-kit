<?php

namespace Overclock\LabKit;

final readonly class Operator
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
