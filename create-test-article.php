<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Tag;
use App\Models\ExternalLink;
use Illuminate\Support\Str;

echo "🔧 Creating a test published article...\n\n";

// Create article
$article = Article::create([
    'slug' => 'test-published-article-' . Str::random(8),
    'user_id' => 3, // Same user as article 1
    'category_id' => 3, // Religion & Philosophy
    'status' => 'published',
    'published_at' => now(),
    'reading_time' => 5,
    'featured_image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&h=600&fit=crop',
]);

// Create translation
ArticleTranslation::create([
    'article_id' => $article->id,
    'locale' => 'bn',
    'title' => 'মহাবিশ্বের সৃষ্টি রহস্য',
    'subtitle' => 'বিজ্ঞান এবং ধর্মের দৃষ্টিকোণ',
    'excerpt' => 'মহাবিশ্ব কীভাবে সৃষ্টি হলো? এই প্রশ্নটি মানুষকে যুগ যুগ ধরে ভাবিয়ে এসেছে।',
    'content' => "মহাবিশ্বের সৃষ্টি নিয়ে বিজ্ঞান এবং ধর্ম উভয়েরই নিজস্ব ব্যাখ্যা রয়েছে।

বিগ ব্যাং তত্ত্ব অনুসারে, প্রায় ১৩.৮ বিলিয়ন বছর আগে একটি বিশাল বিস্ফোরণের মাধ্যমে মহাবিশ্ব সৃষ্টি হয়েছিল।

অন্যদিকে, বিভিন্ন ধর্মীয় গ্রন্থে বলা হয়েছে যে স্রষ্টা মহাবিশ্ব সৃষ্টি করেছেন।

আসলে এই দুটি দৃষ্টিকোণ পরস্পর বিরোধী নয়।
বিজ্ঞান ব্যাখ্যা করে কীভাবে মহাবিশ্ব সৃষ্টি হয়েছে,
আর ধর্ম ব্যাখ্যা করে কেন মহাবিশ্ব সৃষ্টি হয়েছে।"
]);

// Add tags
$tag1 = Tag::where('slug', 'ishwar')->first();
$tag2 = Tag::where('slug', 'dharma')->first();
$tag3 = Tag::firstOrCreate(
    ['slug' => 'bigbang'],
    ['name_bn' => 'বিগ ব্যাং', 'name_en' => 'Big Bang']
);
$tag4 = Tag::firstOrCreate(
    ['slug' => 'science'],
    ['name_bn' => 'বিজ্ঞান', 'name_en' => 'Science']
);

$article->tags()->attach([$tag1->id, $tag2->id, $tag3->id, $tag4->id]);

// Add external link
$link = ExternalLink::firstOrCreate(
    ['url' => 'https://en.wikipedia.org/wiki/Big_Bang'],
    ['title' => 'Big Bang Theory - Wikipedia', 'type' => 'reference']
);
$article->externalLinks()->attach($link->id);

echo "✅ Article created successfully!\n";
echo "   - ID: " . $article->id . "\n";
echo "   - Slug: " . $article->slug . "\n";
echo "   - Status: " . $article->status . "\n";
echo "   - Featured Image: Yes\n";
echo "   - Tags: " . $article->tags()->count() . "\n";
echo "   - External Links: " . $article->externalLinks()->count() . "\n";
echo "\n🌐 View at: http://localhost:3000/bn/articles/" . $article->slug . "\n";
echo "📋 Or go to: http://localhost:3000/bn/public-articles\n";
