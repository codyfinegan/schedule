<?php

declare(strict_types=1);

namespace Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Rsvp extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['game_block_id', 'user_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function gameBlock(): BelongsTo
    {
        return $this->belongsTo(GameBlock::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
