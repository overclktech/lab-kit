<?php

use App\LabKit\UserActions;
use Orchestra\Testbench\Factories\UserFactory;
use Overclock\LabKit\Operator;

beforeEach(function (): void {
    UserActions::$calls = [];
});

it('delegates the user action to the app', function (string $command, string $method): void {
    $user = UserFactory::new()->create();

    expect(lab($command, payload(['user_id' => (string) $user->id])))->toBe([0, ['ok' => true, 'data' => ['user_id' => (string) $user->id]]])
        ->and(UserActions::$calls)->toEqual([[$method, $user->id, new Operator('7', 'Ana')]]);
})->with([
    ['lab:users:block', 'block'],
    ['lab:users:unblock', 'unblock'],
    ['lab:users:force-password-reset', 'forcePasswordReset'],
]);

it('reports an unknown user without calling the app', function (): void {
    expect(lab('lab:users:block', payload(['user_id' => '999'])))->toBe([1, ['ok' => false, 'error' => ['code' => 'not_found', 'message' => 'Usuário não encontrado.']]])
        ->and(UserActions::$calls)->toBe([]);
});
