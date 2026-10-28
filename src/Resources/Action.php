<?php

namespace Overclock\LabKit\Resources;

use Overclock\LabKit\Contracts\ResourceAction;

final readonly class Action
{
    /**
     * @param  class-string<ResourceAction>  $handler
     */
    private function __construct(
        public string $name,
        public string $handler,
        public bool $confirm = false,
        public bool $requiresMfa = false,
    ) {}

    /**
     * @param  class-string<ResourceAction>  $handler
     */
    public static function make(string $name, string $handler): self
    {
        return new self($name, $handler);
    }

    public function confirm(): self
    {
        return new self($this->name, $this->handler, true, $this->requiresMfa);
    }

    public function requiresMfa(): self
    {
        return new self($this->name, $this->handler, true, true);
    }

    /**
     * @return array{name: string, confirm: bool, requires_mfa: bool}
     */
    public function describe(): array
    {
        return ['name' => $this->name, 'confirm' => $this->confirm, 'requires_mfa' => $this->requiresMfa];
    }
}
