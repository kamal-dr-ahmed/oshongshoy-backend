<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;
use App\Models\Tag;

// Update article 1
$article = Article::find(1);

if ($article) {
    // Add featured image
    $article->featured_image = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=600&fit=crop';
    $article->save();
    
    echo "✅ Featured image added\n";
    
    // Create or find tags
    $tag1 = Tag::firstOrCreate(
        ['slug' => 'dharma'],
        ['name_bn' => 'ধর্ম', 'name_en' => 'Religion']
    );
    
    $tag2 = Tag::firstOrCreate(
        ['slug' => 'philosophy'],
        ['name_bn' => 'দর্শন', 'name_en' => 'Philosophy']
    );
    
    $tag3 = Tag::firstOrCreate(
        ['slug' => 'ishwar'],
        ['name_bn' => 'ঈশ্বর', 'name_en' => 'God']
    );
    
    // Attach tags to article
    $article->tags()->sync([$tag1->id, $tag2->id, $tag3->id]);
    
    echo "✅ Tags added: " . $article->tags()->pluck('name_bn')->implode(', ') . "\n";
    echo "✅ Featured Image: " . $article->featured_image . "\n";
    echo "\n🎉 Article 1 updated successfully!\n";
    echo "\nNow refresh your browser and click on the draft article again.\n";
} else {
    echo "❌ Article 1 not found\n";
}
