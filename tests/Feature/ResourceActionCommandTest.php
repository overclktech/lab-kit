<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Overclock\LabKit\LabException;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Tests\Fixtures\Actions\RenameMember;
use Overclock\LabKit\Tests\Fixtures\Models\Member;
use Overclock\LabKit\Tests\Fixtures\Models\Plain;
use Overclock\LabKit\Tests\Fixtures\Policies\MemberPolicy;

beforeEach(function (): void {
    MemberPolicy::$allows = true;
    MemberPolicy::$runs = true;
    RenameMember::$calls = [];
    RenameMember::$fails = false;
    Gate::policy(Member::class, MemberPolicy::class);
    Gate::policy(Plain::class, MemberPolicy::class);
    $this->freezeTime();
    $this->member = Member::query()->create(['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'hash']);
});

function runAction(Model $member, array $overrides = []): array
{
    return lab('lab:resources:action', payload([
        'resource' => 'member',
        'key' => (string) $member->getKey(),
        'action' => 'rename',
        'version' => LabResource::version($member->fresh()),
        'confirmed' => true,
        ...$overrides,
    ]));
}

it('runs the app action on the record and returns its result and new version', function (): void {
    $read = LabResource::version($this->member->fresh());
    [$exitCode, $response] = runAction($this->member);

    expect($exitCode)->toBe(0)
        ->and($response['data']['result'])->toBe(['renamed' => true])
        ->and($response['data']['version'])->toBe(LabResource::version($this->member->fresh()))->not->toBe($read)
        ->and($this->member->fresh()->name)->toBe('Renomeado')
        ->and(RenameMember::$calls)->toBe([[(string) $this->member->getKey(), '7']]);
});

it('refuses a record edited in the same second since it was read', function (): void {
    $read = LabResource::version($this->member->fresh());
    $this->member->forceFill(['email' => 'outra@example.com'])->save();

    [$exitCode, $response] = runAction($this->member, ['version' => $read]);

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('conflict')
        ->and(RenameMember::$calls)->toBe([])
        ->and($this->member->fresh()->name)->toBe('Ana');
});

it('runs only the first of two actions sent with the same version', function (): void {
    $read = LabResource::version($this->member->fresh());

    [$first] = runAction($this->member, ['version' => $read]);
    [$second, $response] = runAction($this->member, ['version' => $read]);

    expect([$first, $second])->toBe([0, 1])
        ->and($response['error']['code'])->toBe('conflict')
        ->and(RenameMember::$calls)->toHaveCount(1);
});

it('protects a model without timestamps the same way', function (): void {
    $plain = Plain::query()->findOrFail($this->member->getKey());
    $read = LabResource::version($plain);
    $plain->forceFill(['email' => 'outra@example.com'])->save();

    [$exitCode, $response] = runAction($plain, ['resource' => 'plain', 'version' => $read]);

    expect($exitCode)->toBe(1)
        ->and($response['error']['code'])->toBe('conflict')
        ->and(RenameMember::$calls)->toBe([]);
});

it('refuses and rolls back an action that leaves the record unchanged', function (): void {
    [$exitCode, $response] = runAction(Plain::query()->findOrFail($this->member->getKey()), ['resource' => 'plain', 'action' => 'touch-nothing']);

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('unchanged');
});

it('requires the version read from the listing', function (): void {
    [$exitCode, $response] = runAction($this->member, ['version' => null]);

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('invalid_input');
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

it('versions binary columns by their content and leaves the stream readable', function (): void {
    $stream = function (string $contents) {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contents);
        rewind($handle);

        return $handle;
    };
    $first = (new Member)->setRawAttributes(['id' => 1, 'avatar' => $stream("\x00\xff")]);
    $second = (new Member)->setRawAttributes(['id' => 1, 'avatar' => $stream("\x01\xff")]);
    $same = (new Member)->setRawAttributes(['id' => 1, 'avatar' => $stream("\x00\xff")]);

    expect(LabResource::version($first))->not->toBe(LabResource::version($second))
        ->and(LabResource::version($first))->toBe(LabResource::version($same))
        ->and(stream_get_contents($first->getAttributes()['avatar']))->toBe("\x00\xff");
});

it('refuses to version a binary column it cannot read again', function (): void {
    $member = (new Member)->setRawAttributes(['id' => 1, 'avatar' => popen('printf x', 'r')]);

    expect(fn (): string => LabResource::version($member))->toThrow(LabException::class);
});
