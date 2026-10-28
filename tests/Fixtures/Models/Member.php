<?php

namespace Overclock\LabKit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
#[Hidden(['password', 'remember_token', 'internal_note'])]
#[Table(name: 'users')]
class Member extends Model {}
