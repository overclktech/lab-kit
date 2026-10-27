<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Support\Arr;
use Overclock\LabKit\Contracts\UserActions;
use Overclock\LabKit\Operator;

#[Description('Obriga um usuário a trocar a senha pelas regras do app')]
#[Signature('lab:users:force-password-reset')]
class ForcePasswordResetUserCommand extends LabCommand
{
    public function __construct(private readonly UserActions $userActions)
    {
        parent::__construct();
    }

    protected function rules(): array
    {
        return ['user_id' => ['required', 'string', 'max:64']];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $user = $this->findUser(Arr::string($params, 'user_id'));

        $this->userActions->forcePasswordReset($user, $operator);

        return ['user_id' => (string) $user->getAuthIdentifier()];
    }
}
