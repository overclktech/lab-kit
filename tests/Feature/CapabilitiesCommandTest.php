<?php

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Artisan;

it('reports the lab kit version and the lab commands as json', function (): void {
    expect(Artisan::call('lab:capabilities'))->toBe(0);

    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'version' => InstalledVersions::getPrettyVersion('overclock/lab-kit'),
        'commands' => [
            'lab:capabilities',
            'lab:resources',
            'lab:resources:action',
            'lab:resources:list',
            'lab:sessions',
            'lab:sessions:revoke',
            'lab:users',
            'lab:users:block',
            'lab:users:force-password-reset',
            'lab:users:unblock',
        ],
    ]);
});
