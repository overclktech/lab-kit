<?php

namespace Overclock\LabKit\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Overclock\LabKit\Operator;

#[Description('Lista os usuários do app, com busca por nome ou e-mail')]
#[Signature('lab:users')]
class UsersCommand extends LabCommand
{
    private const int PER_PAGE = 50;

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    protected function respond(array $params, Operator $operator): array
    {
        $search = Arr::get($params, 'search');
        $page = Arr::get($params, 'page');

        $users = $this->userProvider()->createModel()->newQuery()
            ->when(is_string($search), fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"),
            ))
            ->orderBy('id')
            ->paginate(self::PER_PAGE, page: is_int($page) ? $page : 1);

        return [
            'users' => $users->getCollection()->map(fn (Model $user): array => [
                'id' => (string) $user->getKey(),
                'name' => $user->getAttribute('name'),
                'email' => $user->getAttribute('email'),
                'email_verified_at' => $this->formatDate($user->getAttribute('email_verified_at')),
                'blocked_at' => $this->formatDate($user->getAttribute('blocked_at')),
                'created_at' => $this->formatDate($user->getAttribute('created_at')),
            ])->all(),
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ];
    }
}
