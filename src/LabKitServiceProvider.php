<?php

namespace Overclock\LabKit;

use Illuminate\Support\ServiceProvider;
use Overclock\LabKit\Console\CapabilitiesCommand;

class LabKitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CapabilitiesCommand::class]);
        }
    }
}
