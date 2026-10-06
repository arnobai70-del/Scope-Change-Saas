<?php

namespace App\Enums;

enum TimelineImpact: string
{
    case None = 'none';
    case Days = 'days';
    case Weeks = 'weeks';
    case Date = 'date';
}
