<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

// Publish article 1
$article = Article::find(1);

if ($article) {
    $article->status = 'published';
    $article->published_at = now();
    $article->save();
    
    // Load relationships
    $article->load(['tags', 'externalLinks']);
    
    echo "✅ Article 1 is now PUBLISHED!\n\n";
    echo "📊 Article Details:\n";
    echo "  - Status: " . $article->status . "\n";
    echo "  - Published at: " . $article->published_at . "\n";
    echo "  - Featured Image: " . ($article->featured_image ? 'Yes' : 'No') . "\n";
    echo "  - Tags count: " . $article->tags->count() . "\n";
    
    if ($article->tags->count() > 0) {
        echo "  - Tags: " . $article->tags->pluck('name_bn')->implode(', ') . "\n";
    }
    
    echo "  - External links count: " . $article->externalLinks->count() . "\n";
    
    echo "\n✅ Now you can view this article at:\n";
    echo "   http://localhost:3000/bn/articles/" . $article->slug . "\n\n";
    echo "🔄 Refresh your browser and click 'Read more' again!\n";
} else {
    echo "❌ Article 1 not found\n";
}
