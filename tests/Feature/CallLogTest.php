<?php

use App\LabKit\UserActions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\Factories\UserFactory;

beforeEach(function (): void {
    Log::spy();
});

it('logs the operation, the operator and the result of a call', function (): void {
    lab('lab:users', payload(['search' => 'bia@example.com']));

    Log::shouldHaveReceived('info')->once()->with('Lab Kit call', [
        'command' => 'lab:users',
        'operation_id' => 'op-1',
        'operator_id' => '7',
        'operator_name' => 'Ana',
        'result' => 'ok',
    ]);
});

it('logs the error code of a failed call', function (): void {
    lab('lab:users:block', payload(['user_id' => '999']));

    Log::shouldHaveReceived('warning')->once()->with('Lab Kit call', [
        'command' => 'lab:users:block',
        'operation_id' => 'op-1',
        'operator_id' => '7',
        'operator_name' => 'Ana',
        'result' => 'not_found',
    ]);
});

it('logs an invalid call without trusting its payload', function (): void {
    lab('lab:users', ['operation_id' => 'op-1', 'params' => []]);

    Log::shouldHaveReceived('warning')->once()->with('Lab Kit call', [
        'command' => 'lab:users',
        'operation_id' => null,
        'operator_id' => null,
        'operator_name' => null,
        'result' => 'invalid_input',
    ]);
});

it('never logs params or response data', function (): void {
    $user = UserFactory::new()->create(['email' => 'bia@example.com']);

    lab('lab:users', payload(['search' => 'bia@example.com']));
    lab('lab:sessions', payload(['user_id' => (string) $user->id]));

    Log::shouldHaveReceived('info')->twice()->withArgs(
        fn (string $message, array $context): bool => ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'bia@example.com'),
    );
});

it('logs a call denied by a policy of the app exactly once, without its message', function (): void {
    $user = UserFactory::new()->create();
    UserActions::$throws = new AuthorizationException('Denied for secret-marker.');

    lab('lab:users:block', payload(['user_id' => (string) $user->id]));

    Log::shouldHaveReceived('warning')->once()->with('Lab Kit call', [
        'command' => 'lab:users:block',
        'operation_id' => 'op-1',
        'operator_id' => '7',
        'operator_name' => 'Ana',
        'result' => 'forbidden',
    ]);
});

it('logs a call that fails with an unexpected error, without leaking it', function (): void {
    $user = UserFactory::new()->create();
    UserActions::$throws = new RuntimeException('database password secret-marker');

    [$exitCode, $response] = lab('lab:users:block', payload(['user_id' => (string) $user->id]));

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('internal_error')
        ->and(json_encode($response))->not->toContain('secret-marker');

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $context['result'] === 'internal_error'
            && $context['operation_id'] === 'op-1'
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'secret-marker'),
    );
});

it('does not carry the identity of a previous call into the next one', function (): void {
    lab('lab:users', payload());
    lab('lab:users', ['operation_id' => 'op-2', 'params' => []]);

    Log::shouldHaveReceived('warning')->once()->with('Lab Kit call', [
        'command' => 'lab:users',
        'operation_id' => null,
        'operator_id' => null,
        'operator_name' => null,
        'result' => 'invalid_input',
    ]);
});
