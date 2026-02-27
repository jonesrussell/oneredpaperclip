<?php

namespace App\Enums;

enum ChallengeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Paused = 'paused';
}
