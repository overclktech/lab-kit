<?php

use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Factories\UserFactory;

function openSession(int $userId, string $id, int $lastActivity = 1_700_000_000): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '203.0.113.9',
        'user_agent' => 'Firefox',
        'payload' => 'secret-payload',
        'last_activity' => $lastActivity,
    ]);
}

it('lists sessions by handle without exposing the session id', function (): void {
    $user = UserFactory::new()->create();
    openSession($user->id, 'real-session-id');

    [$exitCode, $response] = lab('lab:sessions', payload(['user_id' => (string) $user->id]));

    expect($exitCode)->toBe(0)
        ->and($response['data']['sessions'])->toBe([[
            'handle' => hash('sha256', 'real-session-id'),
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Firefox',
            'last_activity' => date(DATE_ATOM, 1_700_000_000),
        ]])
        ->and(json_encode($response))->not->toContain('real-session-id')->not->toContain('secret-payload');
});

it('revokes one session by handle', function (): void {
    $user = UserFactory::new()->create();
    openSession($user->id, 'keep');
    openSession($user->id, 'drop');

    expect(lab('lab:sessions:revoke', payload(['user_id' => (string) $user->id, 'handle' => hash('sha256', 'drop')])))->toBe([0, ['ok' => true, 'data' => ['revoked' => 1]]])
        ->and(DB::table('sessions')->pluck('id')->all())->toBe(['keep']);
});

it('does not revoke a session of another user', function (): void {
    $user = UserFactory::new()->create();
    $other = UserFactory::new()->create();
    openSession($other->id, 'other');

    expect(lab('lab:sessions:revoke', payload(['user_id' => (string) $user->id, 'handle' => hash('sha256', 'other')]))[1]['error']['code'])->toBe('not_found')
        ->and(DB::table('sessions')->count())->toBe(1);
});

it('revokes all sessions and rotates the remember token', function (): void {
    $user = UserFactory::new()->create(['remember_token' => 'old-token']);
    openSession($user->id, 'one');
    openSession($user->id, 'two');

    expect(lab('lab:sessions:revoke', payload(['user_id' => (string) $user->id])))->toBe([0, ['ok' => true, 'data' => ['revoked' => 2]]])
        ->and(DB::table('sessions')->count())->toBe(0)
        ->and($user->fresh()->remember_token)->not->toBe('old-token');
});
