<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    protected $fillable = [
        'user_id',
        'blocked_by',
        'block_type',
        'reason',
        'blocked_at',
        'expires_at',
        'is_active',
        'unblock_reason',
        'unblocked_by',
        'unblocked_at',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'unblocked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who is blocked.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin/moderator who blocked the user.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Get the admin/moderator who unblocked the user.
     */
    public function unblocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * Check if block is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->block_type === 'permanent') {
            return true;
        }

        return $this->expires_at && $this->expires_at->isFuture();
    }
}
