<?php

namespace Overclock\LabKit;

use Illuminate\Support\ServiceProvider;
use Overclock\LabKit\Console\BlockUserCommand;
use Overclock\LabKit\Console\CapabilitiesCommand;
use Overclock\LabKit\Console\ForcePasswordResetUserCommand;
use Overclock\LabKit\Console\RevokeSessionsCommand;
use Overclock\LabKit\Console\SessionsCommand;
use Overclock\LabKit\Console\UnblockUserCommand;
use Overclock\LabKit\Console\UsersCommand;
use Overclock\LabKit\Contracts\UserActions;

class LabKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $userActions = $this->app->getNamespace().'LabKit\\UserActions';

        if (is_subclass_of($userActions, UserActions::class)) {
            $this->app->bind(UserActions::class, $userActions);
        }
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([CapabilitiesCommand::class]);

        if ($this->app->bound(UserActions::class)) {
            $this->commands([
                UsersCommand::class,
                SessionsCommand::class,
                RevokeSessionsCommand::class,
                BlockUserCommand::class,
                UnblockUserCommand::class,
                ForcePasswordResetUserCommand::class,
            ]);
        }
    }
}
