<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'user_id',
        'category_id',
        'status',
        'published_at',
        'reading_time',
        'view_count',
        'like_count',
        'rating',
        'rating_count',
        'is_featured',
        'is_trending',
        'featured_image',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'rating' => 'decimal:2',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'article_media')
                    ->withPivot(['sort_order', 'position', 'caption'])
                    ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }

    public function externalLinks(): BelongsToMany
    {
        return $this->belongsToMany(ExternalLink::class, 'article_links')
                    ->withPivot(['context', 'sort_order'])
                    ->withTimestamps();
    }

    // Helper methods
    public function getTranslation($locale = 'bn')
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
