<?php

namespace Overclock\LabKit\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Informa a versão do Lab Kit e os comandos do Lab que este app atende')]
#[Signature('lab:capabilities')]
class CapabilitiesCommand extends Command
{
    public function handle(): int
    {
        $commands = array_keys($this->getApplication()?->all('lab') ?? []);
        sort($commands);

        $this->line(json_encode([
            'version' => InstalledVersions::getPrettyVersion('overclock/lab-kit'),
            'commands' => $commands,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
