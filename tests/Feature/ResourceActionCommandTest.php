<?php

use Illuminate\Support\Facades\Gate;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Tests\Fixtures\Actions\RenameMember;
use Overclock\LabKit\Tests\Fixtures\Models\Member;
use Overclock\LabKit\Tests\Fixtures\Policies\MemberPolicy;

beforeEach(function (): void {
    MemberPolicy::$allows = true;
    MemberPolicy::$runs = true;
    RenameMember::$calls = [];
    RenameMember::$fails = false;
    Gate::policy(Member::class, MemberPolicy::class);
    $this->member = Member::query()->create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'hash']);
});

function runAction(Member $member, array $overrides = []): array
{
    return lab('lab:resources:action', payload([
        'resource' => 'member',
        'key' => (string) $member->getKey(),
        'action' => 'rename',
        'version' => LabResource::version($member),
        'confirmed' => true,
        ...$overrides,
    ]));
}

it('runs the app action on the record and returns its result and new version', function (): void {
    $this->travel(1)->minutes();
    [$exitCode, $response] = runAction($this->member);

    expect($exitCode)->toBe(0)
        ->and($response['data']['result'])->toBe(['renamed' => true])
        ->and($response['data']['version'])->not->toBe(LabResource::version($this->member))
        ->and($this->member->fresh()->name)->toBe('Renomeado')
        ->and(RenameMember::$calls)->toBe([[(string) $this->member->getKey(), '7']]);
});

it('refuses a record edited since it was read', function (): void {
    $read = LabResource::version($this->member);
    $this->travel(1)->minutes();
    $this->member->forceFill(['email' => 'outra@example.com'])->save();

    [$exitCode, $response] = runAction($this->member, ['version' => $read]);

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('conflict')
        ->and(RenameMember::$calls)->toBe([])
        ->and($this->member->fresh()->name)->toBe('Ana');
});

it('requires confirmation and recent two-factor when the action declares them', function (): void {
    [, $unconfirmed] = runAction($this->member, ['confirmed' => false]);
    [, $withoutMfa] = runAction($this->member, ['action' => 'rename-securely']);
    [$exitCode] = runAction($this->member, ['action' => 'rename-securely', 'mfa' => true]);

    expect($unconfirmed['error']['code'])->toBe('confirmation_required')
        ->and($withoutMfa['error']['code'])->toBe('mfa_required')
        ->and($exitCode)->toBe(0)
        ->and(RenameMember::$calls)->toHaveCount(1);
});

it('refuses without running when the model policy denies the action', function (): void {
    MemberPolicy::$runs = false;
    [$exitCode, $response] = runAction($this->member);

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('forbidden')
        ->and(RenameMember::$calls)->toBe([]);
});

it('answers not found for an unknown action or record', function (): void {
    [, $unknownAction] = runAction($this->member, ['action' => 'delete']);
    [, $unknownRecord] = runAction($this->member, ['key' => '999']);

    expect($unknownAction['error']['code'])->toBe('not_found')
        ->and($unknownRecord['error']['code'])->toBe('not_found');
});

it('rolls back what the action changed when it fails, without leaking the error', function (): void {
    RenameMember::$fails = true;
    [$exitCode, $response] = runAction($this->member);

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('internal_error')
        ->and(json_encode($response))->not->toContain('secret-marker')
        ->and($this->member->fresh()->name)->toBe('Ana');
});
