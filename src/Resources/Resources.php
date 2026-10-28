<?php

namespace Overclock\LabKit\Resources;

use Illuminate\Contracts\Foundation\Application;

final class Resources
{
    /**
     * @var array<string, class-string<LabResource>>|null
     */
    private ?array $resources = null;

    public function __construct(private readonly Application $app) {}

    /**
     * @return array<string, LabResource>
     */
    public function all(): array
    {
        return array_map(fn (string $class): LabResource => $this->app->make($class), $this->discover());
    }

    public function find(string $name): ?LabResource
    {
        $class = $this->discover()[$name] ?? null;

        return $class === null ? null : $this->app->make($class);
    }

    /**
     * @return array<string, class-string<LabResource>>
     */
    private function discover(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $this->resources = [];

        foreach (glob($this->app->path('LabKit/*Resource.php')) ?: [] as $file) {
            $class = $this->app->getNamespace().'LabKit\\'.basename($file, '.php');

            if (is_subclass_of($class, LabResource::class)) {
                $this->resources[$class::name()] = $class;
            }
        }

        ksort($this->resources);

        return $this->resources;
    }
}
