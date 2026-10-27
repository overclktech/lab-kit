<?php

use App\LabKit\UserActions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Overclock\LabKit\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->beforeEach(function (): void {
    UserActions::$calls = [];
    UserActions::$denied = [];
    UserActions::$refuseActions = false;
})->in('Feature');

/**
 * @return array{int, array<string, mixed>}
 */
function lab(string $command, mixed $payload): array
{
    $tester = new CommandTester(app(Kernel::class)->all()[$command]);
    $tester->setInputs([is_string($payload) ? $payload : json_encode($payload, JSON_THROW_ON_ERROR)]);
    $exitCode = $tester->execute([]);

    return [$exitCode, json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR)];
}

/**
 * @param  array<string, mixed>  $params
 * @return array<string, mixed>
 */
function payload(array $params = []): array
{
    return ['operation_id' => 'op-1', 'operator' => ['id' => '7', 'name' => 'Ana'], 'params' => $params];
}
