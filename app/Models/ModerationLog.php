<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationLog extends Model
{
    protected $fillable = [
        'article_id',
        'moderator_id',
        'action',
        'comment',
        'previous_status',
        'new_status',
    ];

    /**
     * Get the article that was moderated.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the moderator who performed the action.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
