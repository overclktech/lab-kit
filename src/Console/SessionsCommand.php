<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Overclock\LabKit\Operator;

#[Description('Lista as sessões abertas de um usuário')]
#[Signature('lab:sessions')]
class SessionsCommand extends LabCommand
{
    protected function rules(): array
    {
        return ['user_id' => ['required', 'string', 'max:64']];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $user = $this->findUser(Arr::string($params, 'user_id'));

        return [
            'sessions' => self::sessionsOf($user->getAuthIdentifier())
                ->orderByDesc('last_activity')
                ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
                ->map(fn (object $session): array => [
                    'handle' => self::sessionHandle($session->id),
                    'ip_address' => $session->ip_address,
                    'user_agent' => Str::limit((string) $session->user_agent, 255),
                    'last_activity' => date(DATE_ATOM, (int) $session->last_activity),
                ])->all(),
        ];
    }

    public static function sessionsOf(mixed $userId): Builder
    {
        return DB::connection(Config::get('session.connection'))
            ->table(Config::string('session.table'))
            ->where('user_id', $userId);
    }

    public static function sessionHandle(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }
}
