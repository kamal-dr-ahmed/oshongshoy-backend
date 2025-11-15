<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$article = \App\Models\Article::find(3);
$tags = ['TestTag1', 'TestTag2'];
$tagIds = [];

foreach ($tags as $tagName) {
    $slug = \Str::slug($tagName);
    $tag = \App\Models\Tag::where('slug', $slug)->first();
    
    if (!$tag) {
        $tag = \App\Models\Tag::create([
            'name_bn' => $tagName,
            'name_en' => $tagName,
            'slug' => $slug
        ]);
        echo "Created tag: {$tagName} (ID: {$tag->id})\n";
    } else {
        echo "Found existing tag: {$tagName} (ID: {$tag->id})\n";
    }
    
    $tagIds[] = $tag->id;
}

echo "Tag IDs to sync: " . json_encode($tagIds) . "\n";

$article->tags()->sync($tagIds);

echo "After sync:\n";
echo json_encode($article->load('tags')->tags, JSON_PRETTY_PRINT);
echo "\n";

// Check pivot table directly
echo "\nPivot table records:\n";
echo json_encode(\DB::table('article_tags')->where('article_id', 3)->get(), JSON_PRETTY_PRINT);
