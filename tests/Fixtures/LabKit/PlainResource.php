<?php

namespace App\LabKit;

use Overclock\LabKit\Resources\Action;
use Overclock\LabKit\Resources\Field;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Tests\Fixtures\Actions\RenameMember;
use Overclock\LabKit\Tests\Fixtures\Actions\TouchNothing;
use Overclock\LabKit\Tests\Fixtures\Models\Plain;

class PlainResource extends LabResource
{
    public static string $model = Plain::class;

    public function fields(): array
    {
        return [Field::id(), Field::text('name')];
    }

    public function actions(): array
    {
        return [
            Action::make('rename', RenameMember::class)->confirm(),
            Action::make('touch-nothing', TouchNothing::class),
        ];
    }
}
