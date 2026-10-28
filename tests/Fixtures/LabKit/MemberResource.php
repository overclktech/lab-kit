<?php

namespace App\LabKit;

use Overclock\LabKit\Resources\Action;
use Overclock\LabKit\Resources\Field;
use Overclock\LabKit\Resources\LabResource;
use Overclock\LabKit\Tests\Fixtures\Actions\RenameMember;
use Overclock\LabKit\Tests\Fixtures\Models\Member;

class MemberResource extends LabResource
{
    public static string $model = Member::class;

    public static int $perPage = 2;

    public function fields(): array
    {
        return [Field::id(), Field::text('name')->filterable(), Field::text('email'), Field::dateTime('created_at')];
    }

    public function actions(): array
    {
        return [
            Action::make('rename', RenameMember::class)->confirm(),
            Action::make('rename-securely', RenameMember::class)->requiresMfa(),
        ];
    }
}
