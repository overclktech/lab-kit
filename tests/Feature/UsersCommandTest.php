<?php

use Orchestra\Testbench\Factories\UserFactory;

it('lists users with public fields only', function (): void {
    $user = UserFactory::new()->create(['name' => 'Bia', 'email' => 'bia@example.com']);

    [$exitCode, $response] = lab('lab:users', payload());

    expect($exitCode)->toBe(0)
        ->and($response['data']['total'])->toBe(1)
        ->and($response['data']['users'][0])->toBe([
            'id' => (string) $user->id,
            'name' => 'Bia',
            'email' => 'bia@example.com',
            'email_verified_at' => $user->email_verified_at->format(DATE_ATOM),
            'blocked_at' => null,
            'created_at' => $user->created_at->format(DATE_ATOM),
        ]);
});

it('searches users by name or email', function (): void {
    UserFactory::new()->create(['name' => 'Bia', 'email' => 'bia@example.com']);
    UserFactory::new()->create(['name' => 'Caio', 'email' => 'caio@empresa.com']);

    expect(lab('lab:users', payload(['search' => 'empresa']))[1]['data']['users'])->sequence(
        fn ($user) => $user->name->toBe('Caio'),
    );
});

it('paginates fifty users per page', function (): void {
    UserFactory::new()->count(51)->create();

    $data = lab('lab:users', payload(['page' => 2]))[1]['data'];

    expect($data['users'])->toHaveCount(1)->and($data['per_page'])->toBe(50)->and($data['total'])->toBe(51);
});
