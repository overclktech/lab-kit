<?php

namespace Overclock\LabKit\Resources;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

final readonly class Field
{
    private const string SENSITIVE = '/password|secret|token|remember|two_factor|recovery|private_key|api_key/i';

    private function __construct(
        public string $name,
        public FieldType $type,
        public bool $filterable = false,
    ) {
        if (preg_match(self::SENSITIVE, $name) === 1) {
            throw new InvalidArgumentException("The field {$name} holds sensitive data and cannot be declared.");
        }
    }

    public static function id(string $name = 'id'): self
    {
        return new self($name, FieldType::Id);
    }

    public static function text(string $name): self
    {
        return new self($name, FieldType::Text);
    }

    public static function number(string $name): self
    {
        return new self($name, FieldType::Number);
    }

    public static function money(string $name): self
    {
        return new self($name, FieldType::Money);
    }

    public static function status(string $name): self
    {
        return new self($name, FieldType::Status);
    }

    public static function boolean(string $name): self
    {
        return new self($name, FieldType::Boolean);
    }

    public static function dateTime(string $name): self
    {
        return new self($name, FieldType::DateTime);
    }

    public function filterable(): self
    {
        return new self($this->name, $this->type, true);
    }

    /**
     * @return array{name: string, type: string, filterable: bool}
     */
    public function describe(): array
    {
        return ['name' => $this->name, 'type' => $this->type->value, 'filterable' => $this->filterable];
    }

    public function present(Model $record): string|int|float|bool|null
    {
        $value = $record->getAttribute($this->name);

        return match (true) {
            $value === null => null,
            $this->type === FieldType::DateTime && ($value instanceof DateTimeInterface || is_string($value)) => Date::parse($value)->format(DATE_ATOM),
            $value instanceof BackedEnum => $value->value,
            is_scalar($value) => $value,
            default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        };
    }
}
