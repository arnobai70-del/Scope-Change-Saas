<?php

namespace App\Enums;

enum ActorType: string
{
    case User = 'user';
    case Client = 'client';
    case System = 'system';
    case Admin = 'admin';
    case Provider = 'provider';
}
