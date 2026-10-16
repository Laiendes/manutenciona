<?php

namespace App\Models;

use App\Enums\MaintenanceRequestPriorityEnum;
use App\Enums\MaintenanceRequestStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'priority',
        'status',
        'requester_id',
        'assignee_id',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceRequestHistory::class);
    }

    protected function casts(): array
    {
        return [
            'priority' => MaintenanceRequestPriorityEnum::class,
            'status' => MaintenanceRequestStatusEnum::class,
        ];
    }
}
