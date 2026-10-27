<?php

use App\LabKit\UserActions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Factories\UserFactory;
use Overclock\LabKit\Ability;

it('refuses to list users when the app denies it, without returning personal data', function (): void {
    UserFactory::new()->create(['email' => 'bia@example.com']);
    UserActions::$denied = [Ability::ViewUsers];

    [$exitCode, $response] = lab('lab:users', payload());

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('forbidden')
        ->and(json_encode($response))->not->toContain('bia@example.com');
});

it('refuses to list sessions when the app denies it', function (): void {
    $user = UserFactory::new()->create();
    DB::table('sessions')->insert(['id' => 'real-id', 'user_id' => $user->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => 1]);
    UserActions::$denied = [Ability::ViewSessions];

    [$exitCode, $response] = lab('lab:sessions', payload(['user_id' => (string) $user->id]));

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('forbidden')
        ->and(json_encode($response))->not->toContain('203.0.113.9');
});

it('refuses to revoke sessions when the app denies it, changing nothing', function (): void {
    $user = UserFactory::new()->create(['remember_token' => 'old-token']);
    DB::table('sessions')->insert(['id' => 'real-id', 'user_id' => $user->id, 'payload' => '', 'last_activity' => 1]);
    UserActions::$denied = [Ability::RevokeSessions];

    [$exitCode, $response] = lab('lab:sessions:revoke', payload(['user_id' => (string) $user->id]));

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('forbidden')
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and($user->fresh()->remember_token)->toBe('old-token');
});

it('answers forbidden when a policy of the app denies an action', function (): void {
    $user = UserFactory::new()->create();
    UserActions::$throws = new AuthorizationException('Policy denied for user secret-marker.');

    [$exitCode, $response] = lab('lab:users:block', payload(['user_id' => (string) $user->id]));

    expect($exitCode)->toBe(1)
        ->and($response)->toBe(['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'O app não autorizou esta operação para o operador.']])
        ->and(json_encode($response))->not->toContain('secret-marker');
});

it('allows each ability when the app allows it', function (): void {
    $user = UserFactory::new()->create();

    expect(lab('lab:users', payload())[0])->toBe(0)
        ->and(lab('lab:sessions', payload(['user_id' => (string) $user->id]))[0])->toBe(0)
        ->and(lab('lab:sessions:revoke', payload(['user_id' => (string) $user->id]))[0])->toBe(0);
});
