<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Media extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'duration',
        'video_url',
        'thumbnail_path',
        'alt_text',
        'caption',
        'credit',
        'source_url',
        'uploaded_by',
    ];

    // Relationships
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_media')
            ->withPivot(['sort_order', 'position', 'caption'])
            ->withTimestamps();
    }
}
