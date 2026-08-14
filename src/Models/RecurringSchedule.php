<?php

declare(strict_types=1);

namespace Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RecurringSchedule extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'day_of_week',
        'start_time',
        'duration_minutes',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function gameBlocks(): HasMany
    {
        return $this->hasMany(GameBlock::class);
    }
}
