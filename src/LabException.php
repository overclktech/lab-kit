<?php

namespace Overclock\LabKit;

use RuntimeException;

final class LabException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function notFound(string $message): self
    {
        return new self('not_found', $message);
    }
}
