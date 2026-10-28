<?php

use Illuminate\Support\Facades\Gate;
use Overclock\LabKit\Operator;
use Overclock\LabKit\Resources\Field;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Tests\Fixtures\Models\Member;
use Overclock\LabKit\Tests\Fixtures\Models\Undecided;
use Overclock\LabKit\Tests\Fixtures\Policies\MemberPolicy;

beforeEach(function (): void {
    MemberPolicy::$allows = true;
    Gate::policy(Member::class, MemberPolicy::class);

    foreach (['Ana', 'Bruno', 'Carla'] as $index => $name) {
        Member::query()->create([
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'password' => 'hash-secreto-'.$index,
            'remember_token' => 'token-secreto-'.$index,
        ]);
    }
});

it('describes the resources and fields the operator may see', function (): void {
    [$exitCode, $response] = lab('lab:resources', payload());

    expect($exitCode)->toBe(0)
        ->and($response['data']['resources'])->toBe([[
            'name' => 'member',
            'fields' => [
                ['name' => 'id', 'type' => 'id', 'filterable' => false],
                ['name' => 'name', 'type' => 'text', 'filterable' => true],
                ['name' => 'email', 'type' => 'text', 'filterable' => false],
                ['name' => 'created_at', 'type' => 'datetime', 'filterable' => false],
            ],
        ]]);
});

it('lists records newest first with only the declared fields and paginates', function (): void {
    [$exitCode, $response] = lab('lab:resources:list', payload(['resource' => 'member', 'page' => 2]));

    expect($exitCode)->toBe(0)
        ->and($response['data'])->toMatchArray(['resource' => 'member', 'page' => 2, 'per_page' => 2, 'total' => 3])
        ->and(array_column($response['data']['rows'], 'name'))->toBe(['Ana'])
        ->and(array_keys($response['data']['rows'][0]))->toBe(['id', 'name', 'email', 'created_at'])
        ->and(json_encode($response))->not->toContain('secreto');
});

it('filters only by filterable fields', function (): void {
    [, $filtered] = lab('lab:resources:list', payload(['resource' => 'member', 'filters' => ['name' => 'Bruno']]));
    [$exitCode, $refused] = lab('lab:resources:list', payload(['resource' => 'member', 'filters' => ['email' => 'ana@example.com']]));

    expect(array_column($filtered['data']['rows'], 'email'))->toBe(['bruno@example.com'])
        ->and($exitCode)->toBe(1)
        ->and($refused['error']['code'])->toBe('invalid_input');
});

it('refuses without data when the model policy denies', function (): void {
    MemberPolicy::$allows = false;
    [$exitCode, $response] = lab('lab:resources:list', payload(['resource' => 'member']));
    [, $described] = lab('lab:resources', payload());

    expect($exitCode)->toBe(1)
        ->and($response)->toBe(['ok' => false, 'error' => ['code' => 'forbidden', 'message' => 'O app não autorizou esta operação para o operador.']])
        ->and($described['data']['resources'])->toBe([]);
});

it('refuses a model whose policy does not decide for the lab', function (): void {
    $resource = new class extends LabResource
    {
        public static string $model = Undecided::class;

        public function fields(): array
        {
            return [Field::id()];
        }
    };

    expect($resource->allows(new Operator('7', 'Ana')))->toBeFalse();
});

it('answers not found for an unknown resource', function (): void {
    [$exitCode, $response] = lab('lab:resources:list', payload(['resource' => 'orders']));

    expect($exitCode)->toBe(1)->and($response['error']['code'])->toBe('not_found');
});

it('refuses sensitive fields by name and fields hidden on the model', function (): void {
    expect(fn (): Field => Field::text('password'))->toThrow(InvalidArgumentException::class)
        ->and(fn (): Field => Field::text('two_factor_secret'))->toThrow(InvalidArgumentException::class)
        ->and(fn (): Field => Field::text('api_token'))->toThrow(InvalidArgumentException::class);

    $resource = new class extends LabResource
    {
        public static string $model = Member::class;

        public function fields(): array
        {
            return [Field::id(), Field::text('internal_note')];
        }
    };

    expect(fn (): array => $resource->visibleFields())->toThrow(InvalidArgumentException::class);
});
