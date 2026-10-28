<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Arr;
use Overclock\LabKit\Contracts\ResourceAction;
use Overclock\LabKit\LabException;
use Overclock\LabKit\Operator;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Resources\Resources;

#[Description('Executa uma ação de um recurso, pelo código do app, num registro na versão informada')]
#[Signature('lab:resources:action')]
class ResourceActionCommand extends LabCommand
{
    public function __construct(private readonly Resources $resources)
    {
        parent::__construct();
    }

    protected function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'max:64'],
            'key' => ['required', 'string', 'max:64'],
            'action' => ['required', 'string', 'max:64'],
            'version' => ['present', 'nullable', 'string', 'max:64'],
            'confirmed' => ['sometimes', 'boolean'],
            'mfa' => ['sometimes', 'boolean'],
        ];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $resource = $this->resources->find(Arr::string($params, 'resource')) ?? throw LabException::notFound('Recurso não encontrado.');
        $action = $resource->action(Arr::string($params, 'action')) ?? throw LabException::notFound('Ação não encontrada.');

        if ($action->confirm && Arr::get($params, 'confirmed') !== true) {
            throw new LabException('confirmation_required', 'Esta ação precisa ser confirmada pelo operador.');
        }

        if ($action->requiresMfa && Arr::get($params, 'mfa') !== true) {
            throw new LabException('mfa_required', 'Esta ação precisa de verificação em duas etapas recente do operador.');
        }

        $handler = app($action->handler);

        if (! $handler instanceof ResourceAction) {
            throw new LabException('unsupported', 'A ação não implementa o contrato de ações do Lab Kit.');
        }

        return $resource->query()->getModel()->getConnection()->transaction(function () use ($resource, $action, $handler, $params, $operator): array {
            $record = $resource->query()->whereKey(Arr::string($params, 'key'))->lockForUpdate()->first()
                ?? throw LabException::notFound('Registro não encontrado.');

            if (! $resource->allowsAction($operator, $record, $action)) {
                $this->forbid();
            }

            if (LabResource::version($record) !== Arr::get($params, 'version')) {
                throw new LabException('conflict', 'O registro mudou desde a leitura. Atualize e tente de novo.');
            }

            $result = $handler->handle($record, $operator);
            $record = $record->fresh() ?? $record;

            return ['result' => $result, 'key' => (string) $record->getKey(), 'version' => LabResource::version($record)];
        });
    }
}
