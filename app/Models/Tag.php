<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name_bn',
        'name_en',
        'slug',
        'description',
        'color',
        'usage_count',
        'is_featured',
    ];

    /**
     * Get articles associated with this tag
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tags');
    }
}
