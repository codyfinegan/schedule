<?php

declare(strict_types=1);

namespace Schedule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Carbon starts_at
 * @property Carbon ends_at
 * @property string botc_app_code
 * @property int recurring_schedule_id
 * @property int created_by_user_id
 * @property-read HasMany rsvps
 * @property-read Carbon created_at
 */
final class GameBlock extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'starts_at',
        'ends_at',
        'botc_app_code',
        'recurring_schedule_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    /**
     * The single locking rule used consistently for RSVP-locking and
     * list splitting: a block is "past" once ends_at has passed.
     */
    public function isPast(): bool
    {
        return $this->ends_at->lt(Carbon::now('UTC'));
    }
}
