<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExternalLink extends Model
{
    protected $fillable = [
        'url',
        'title',
        'type',
        'description',
    ];

    /**
     * Get articles associated with this external link
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_links')
                    ->withPivot(['context', 'sort_order'])
                    ->withTimestamps();
    }
}
