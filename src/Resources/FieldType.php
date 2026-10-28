<?php

namespace Overclock\LabKit\Resources;

enum FieldType: string
{
    case Id = 'id';
    case Text = 'text';
    case Number = 'number';
    case Money = 'money';
    case Status = 'status';
    case Boolean = 'boolean';
    case DateTime = 'datetime';
}
