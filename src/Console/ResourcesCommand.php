<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Overclock\LabKit\Operator;
use Overclock\LabKit\Resources\Field;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Resources\Resources;

#[Description('Lista os recursos de dados que o app declarou e que o operador pode ver')]
#[Signature('lab:resources')]
class ResourcesCommand extends LabCommand
{
    public function __construct(private readonly Resources $resources)
    {
        parent::__construct();
    }

    protected function rules(): array
    {
        return [];
    }

    protected function respond(array $params, Operator $operator): array
    {
        return [
            'resources' => collect($this->resources->all())
                ->filter(fn (LabResource $resource): bool => $resource->allows($operator))
                ->map(fn (LabResource $resource, string $name): array => [
                    'name' => $name,
                    'fields' => array_map(fn (Field $field): array => $field->describe(), $resource->visibleFields()),
                ])
                ->values()
                ->all(),
        ];
    }
}
