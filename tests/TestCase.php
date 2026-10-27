<?php

namespace Overclock\LabKit\Tests;

use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;
use Overclock\LabKit\LabKitServiceProvider;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LabKitServiceProvider::class];
    }
}
