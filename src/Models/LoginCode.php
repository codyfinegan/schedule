<?php

declare(strict_types=1);

namespace Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LoginCode extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'code_hash', 'expires_at', 'consumed_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
