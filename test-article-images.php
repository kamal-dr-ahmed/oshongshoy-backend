<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;

// Get latest article
$article = Article::latest()->first();

if (!$article) {
    echo "No articles found\n";
    exit;
}

echo "=== Article #" . $article->id . " ===\n";
echo "Title: " . $article->translations()->first()?->title . "\n";
echo "Featured Image: " . ($article->featured_image ? substr($article->featured_image, 0, 80) . "..." : "None") . "\n";
echo "\n";

// Get translation and check content
$translation = $article->translations()->first();
if ($translation && $translation->content) {
    echo "=== Content Analysis ===\n";
    echo "Content length: " . strlen($translation->content) . " chars\n";

    // Extract markdown images
    preg_match_all('/!\[([^\]]*)\]\(([^\)]+)\)/m', $translation->content, $matches);
    echo "Markdown images found: " . count($matches[2]) . "\n";

    if (!empty($matches[2])) {
        echo "\nImage URLs in content:\n";
        foreach ($matches[2] as $i => $url) {
            echo "  [$i] " . substr($url, 0, 100) . (strlen($url) > 100 ? "..." : "") . "\n";
        }
    }
}

// Check media relationship
echo "\n=== Media Relationship ===\n";
$mediaCount = $article->media()->count();
echo "Media count in article_media table: " . $mediaCount . "\n";

if ($mediaCount > 0) {
    echo "\nMedia details:\n";
    foreach ($article->media as $media) {
        echo "  ID: " . $media->id . "\n";
        echo "  Title: " . $media->title . "\n";
        echo "  Path: " . substr($media->file_path, 0, 80) . (strlen($media->file_path) > 80 ? "..." : "") . "\n";
        echo "  Alt Text: " . ($media->alt_text ?? "None") . "\n";
        echo "  Pivot sort_order: " . ($media->pivot->sort_order ?? "None") . "\n";
        echo "  Pivot position: " . ($media->pivot->position ?? "None") . "\n";
        echo "\n";
    }
} else {
    echo "No media records linked to this article\n";
    echo "\nNote: Images may have been saved after this feature was added.\n";
    echo "Create a new article with images to test the fix.\n";
}
