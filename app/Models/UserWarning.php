<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWarning extends Model
{
    protected $fillable = [
        'user_id',
        'issued_by',
        'severity',
        'title',
        'reason',
        'is_read',
        'expires_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who received the warning.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin/moderator who issued the warning.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Check if warning is still active
     */
    public function isActive(): bool
    {
        if (!$this->expires_at) {
            return true; // Permanent warning
        }
        
        return $this->expires_at->isFuture();
    }
}
