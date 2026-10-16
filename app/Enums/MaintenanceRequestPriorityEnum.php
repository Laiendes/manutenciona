<?php

namespace App\Enums;

enum MaintenanceRequestPriorityEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}
