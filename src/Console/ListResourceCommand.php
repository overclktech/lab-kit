<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Overclock\LabKit\LabException;
use Overclock\LabKit\Operator;
use Overclock\LabKit\Resources\Field;
use Overclock\LabKit\Resources\Resources;

#[Description('Lista os registros de um recurso, com filtros e paginação')]
#[Signature('lab:resources:list')]
class ListResourceCommand extends LabCommand
{
    public function __construct(private readonly Resources $resources)
    {
        parent::__construct();
    }

    protected function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'max:64'],
            'filters' => ['nullable', 'array', 'max:20'],
            'filters.*' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $name = Arr::string($params, 'resource');
        $resource = $this->resources->find($name) ?? throw LabException::notFound('Recurso não encontrado.');

        if (! $resource->allows($operator)) {
            $this->forbid();
        }

        $fields = collect($resource->visibleFields())->keyBy(fn (Field $field): string => $field->name);
        $query = $resource->query();

        foreach ((array) Arr::get($params, 'filters', []) as $field => $value) {
            if (! $fields->get((string) $field)?->filterable) {
                throw new LabException('invalid_input', "O campo {$field} não aceita filtro.");
            }

            $query->where((string) $field, $value);
        }

        $page = Arr::get($params, 'page');
        $records = $query->orderByDesc($query->getModel()->getKeyName())
            ->paginate($resource::$perPage, page: is_int($page) ? $page : 1);

        return [
            'resource' => $name,
            'rows' => $records->getCollection()->map(
                fn (Model $record): array => $fields->map(fn (Field $field): string|int|float|bool|null => $field->present($record))->all(),
            )->all(),
            'page' => $records->currentPage(),
            'per_page' => $records->perPage(),
            'total' => $records->total(),
        ];
    }
}
