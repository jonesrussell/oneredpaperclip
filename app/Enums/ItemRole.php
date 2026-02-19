<?php

namespace App\Enums;

enum ItemRole: string
{
    case Start = 'start';
    case Goal = 'goal';
    case Offered = 'offered';
}
