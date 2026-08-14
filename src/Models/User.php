<?php

declare(strict_types=1);

namespace Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class User extends Model
{
    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = ['email', 'display_name', 'is_admin', 'is_approved'];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function loginCodes(): HasMany
    {
        return $this->hasMany(LoginCode::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }
}
