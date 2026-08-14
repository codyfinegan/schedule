<?php

declare(strict_types=1);

namespace Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Session extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'token_hash', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
