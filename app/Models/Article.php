<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

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
        'moderated_by',
        'moderated_at',
        'moderation_notes',
        'submitted_at',
        'revision_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
        'submitted_at' => 'datetime',
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

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(ModerationLog::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    // Helper methods
    public function getTranslation($locale = 'bn')
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Status checking methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function needsChanges(): bool
    {
        return $this->status === 'changes_requested';
    }
}
