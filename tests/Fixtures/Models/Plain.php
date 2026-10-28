<?php

namespace Overclock\LabKit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
#[Table(name: 'users')]
#[WithoutTimestamps]
class Plain extends Model {}
