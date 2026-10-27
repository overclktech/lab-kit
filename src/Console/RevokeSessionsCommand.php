<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Overclock\LabKit\Ability;
use Overclock\LabKit\LabException;
use Overclock\LabKit\Operator;

#[Description('Encerra uma sessão de um usuário, ou todas quando nenhuma é informada')]
#[Signature('lab:sessions:revoke')]
class RevokeSessionsCommand extends LabCommand
{
    protected function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'max:64'],
            'handle' => ['nullable', 'string', 'size:64'],
        ];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $user = $this->findUser(Arr::string($params, 'user_id'));
        $this->authorize(Ability::RevokeSessions, $operator, $user);
        $sessions = SessionsCommand::sessionsOf($user->getAuthIdentifier());

        $handle = Arr::get($params, 'handle');

        if (is_string($handle)) {
            $sessionId = $sessions->pluck('id')->first(
                fn (string $id): bool => hash_equals(SessionsCommand::sessionHandle($id), $handle),
            ) ?? throw LabException::notFound('Sessão não encontrada.');

            return ['revoked' => SessionsCommand::sessionsOf($user->getAuthIdentifier())->where('id', $sessionId)->delete()];
        }

        $revoked = $sessions->delete();

        if ($user instanceof Model && $user->getRememberTokenName() !== '') {
            $user->setRememberToken(Str::random(60));
            $user->save();
        }

        return ['revoked' => $revoked];
    }
}
