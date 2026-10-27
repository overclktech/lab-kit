<?php

use Overclock\LabKit\LabKitServiceProvider;

it('registers the service provider', function (): void {
    expect(app()->getProvider(LabKitServiceProvider::class))->toBeInstanceOf(LabKitServiceProvider::class);
});
