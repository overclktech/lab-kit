<?php

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
