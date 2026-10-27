<?php

namespace Overclock\LabKit\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Overclock\LabKit\LabKitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LabKitServiceProvider::class];
    }
}
