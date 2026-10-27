<?php

namespace Overclock\LabKit\Console;

use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonException;
use Overclock\LabKit\Ability;
use Overclock\LabKit\Contracts\UserActions;
use Overclock\LabKit\LabException;
use Overclock\LabKit\Operator;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Throwable;

abstract class LabCommand extends Command
{
    private const int MAX_INPUT_BYTES = 65536;

    private const string FORBIDDEN_MESSAGE = 'O app não autorizou esta operação para o operador.';

    private ?string $operationId = null;

    private ?Operator $operator = null;

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    abstract protected function respond(array $params, Operator $operator): array;

    public function handle(): int
    {
        $this->operationId = null;
        $this->operator = null;
        $input = $this->readInput();

        if (strlen($input) > self::MAX_INPUT_BYTES) {
            return $this->reject('input_too_large', 'A entrada passa do limite de 64 KiB.');
        }

        try {
            $payload = json_decode($input, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->reject('invalid_json', 'A entrada não é um JSON válido.');
        }

        $validator = Validator::make(['payload' => $payload], $this->payloadRules());

        if ($validator->fails()) {
            return $this->reject('invalid_input', $validator->errors()->first());
        }

        $validated = $validator->validated();
        $this->operationId = Arr::string($validated, 'payload.operation_id');
        $this->operator = new Operator(Arr::string($validated, 'payload.operator.id'), Arr::string($validated, 'payload.operator.name'));

        try {
            $data = $this->respond(Arr::array($validated, 'payload.params'), $this->operator);
        } catch (LabException $exception) {
            return $this->reject($exception->reason, $exception->getMessage());
        } catch (AuthorizationException) {
            return $this->reject('forbidden', self::FORBIDDEN_MESSAGE);
        } catch (Throwable) {
            return $this->reject('internal_error', 'O app encontrou um erro ao executar o comando.');
        }

        $this->line(json_encode(['ok' => true, 'data' => $data], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        Log::info('Lab Kit call', $this->callContext('ok'));

        return self::SUCCESS;
    }

    protected function authorize(Ability $ability, Operator $operator, ?Authenticatable $user = null): void
    {
        if (! app(UserActions::class)->allows($ability, $operator, $user)) {
            throw new LabException('forbidden', self::FORBIDDEN_MESSAGE);
        }
    }

    protected function userProvider(): EloquentUserProvider
    {
        $provider = Auth::createUserProvider('users');

        return $provider instanceof EloquentUserProvider
            ? $provider
            : throw new LabException('unsupported', 'O Lab Kit precisa do provedor de usuários do Eloquent.');
    }

    protected function findUser(string $id): Authenticatable
    {
        return $this->userProvider()->retrieveById($id) ?? throw LabException::notFound('Usuário não encontrado.');
    }

    protected function formatDate(DateTimeInterface|string|null $value): ?string
    {
        return $value === null ? null : Date::parse($value)->format(DATE_ATOM);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadRules(): array
    {
        $params = array_filter(array_keys($this->rules()), fn (string $key): bool => ! str_contains($key, '.'));

        return [
            'payload' => ['required', 'array:operation_id,operator,params'],
            'payload.operation_id' => ['required', 'string', 'max:64'],
            'payload.operator' => ['required', 'array:id,name'],
            'payload.operator.id' => ['required', 'string', 'max:64'],
            'payload.operator.name' => ['required', 'string', 'max:255'],
            'payload.params' => $params === [] ? ['present', 'array', 'max:0'] : ['present', 'array:'.implode(',', $params)],
            ...collect($this->rules())->mapWithKeys(fn (mixed $rules, string $key): array => ["payload.params.{$key}" => $rules])->all(),
        ];
    }

    private function readInput(): string
    {
        $stream = $this->input instanceof StreamableInputInterface ? $this->input->getStream() : null;

        return (string) stream_get_contents($stream ?? STDIN, self::MAX_INPUT_BYTES + 1);
    }

    private function reject(string $code, string $message): int
    {
        $this->line(json_encode(['ok' => false, 'error' => ['code' => $code, 'message' => $message]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        Log::warning('Lab Kit call', $this->callContext($code));

        return self::FAILURE;
    }

    /**
     * @return array<string, string|null>
     */
    private function callContext(string $result): array
    {
        return [
            'command' => $this->getName(),
            'operation_id' => $this->operationId,
            'operator_id' => $this->operator?->id,
            'operator_name' => $this->operator?->name,
            'result' => $result,
        ];
    }
}
