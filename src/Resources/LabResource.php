<?php

namespace Overclock\LabKit\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Overclock\LabKit\Operator;

abstract class LabResource
{
    /**
     * @var class-string<Model>
     */
    public static string $model;

    public static int $perPage = 25;

    /**
     * @return list<Field>
     */
    abstract public function fields(): array;

    public static function name(): string
    {
        return Str::kebab(Str::beforeLast(class_basename(static::class), 'Resource'));
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function action(string $name): ?Action
    {
        return collect($this->actions())->first(fn (Action $action): bool => $action->name === $name);
    }

    public function allows(Operator $operator): bool
    {
        $policy = Gate::getPolicyFor(static::$model);

        return is_object($policy) && method_exists($policy, 'labViewAny') && $policy->labViewAny($operator) === true;
    }

    public function allowsAction(Operator $operator, Model $record, Action $action): bool
    {
        $policy = Gate::getPolicyFor(static::$model);

        return is_object($policy) && method_exists($policy, 'labRun') && $policy->labRun($operator, $record, $action->name) === true;
    }

    public static function version(Model $record): string
    {
        $attributes = $record->getAttributes();
        ksort($attributes);

        return hash_hmac('sha256', $record::class.serialize($attributes), app('encrypter')->getKey());
    }

    /**
     * @return list<Field>
     */
    public function visibleFields(): array
    {
        $hidden = (new static::$model)->getHidden();

        foreach ($this->fields() as $field) {
            if (in_array($field->name, $hidden, true)) {
                throw new InvalidArgumentException("The field {$field->name} is hidden on the model and cannot be declared.");
            }
        }

        return $this->fields();
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return static::$model::query();
    }
}
