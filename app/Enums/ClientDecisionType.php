<?php

namespace App\Enums;

enum ClientDecisionType: string
{
    case Approved = 'approved';
    case Declined = 'declined';
}
