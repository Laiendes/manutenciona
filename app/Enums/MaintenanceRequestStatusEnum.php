<?php

namespace App\Enums;

enum MaintenanceRequestStatusEnum: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
