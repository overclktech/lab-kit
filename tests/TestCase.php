<?php

namespace Overclock\LabKit\Tests;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Overclock\LabKit\LabKitServiceProvider;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    protected function defineEnvironment($app): void
    {
        $app->useAppPath(__DIR__.'/Fixtures');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }

    protected function getPackageProviders($app): array
    {
        return [LabKitServiceProvider::class];
    }
}
