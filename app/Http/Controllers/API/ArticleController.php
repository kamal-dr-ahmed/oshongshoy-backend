<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['user', 'category', 'translations', 'tags']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->published(); // Default to published articles
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->featured();
        }

        // Search by title/content
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->whereHas('translations', function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by locale
        $locale = $request->get('locale', 'bn');

        $articles = $query->orderBy('published_at', 'desc')
                         ->paginate(12);

        // Add translation for each article
        $articles->getCollection()->transform(function ($article) use ($locale) {
            $article->translation = $article->getTranslation($locale);
            return $article;
        });

        return response()->json($articles);
    }

    public function show($slug, Request $request)
    {
        $locale = $request->get('locale', 'bn');
        
        $article = Article::with([
            'user', 
            'category', 
            'translations', 
            'media', 
            'tags',
            'externalLinks'
        ])->where('slug', $slug)->firstOrFail();

        // Increment view count
        $article->increment('view_count');

        // Get translation for requested locale
        $article->translation = $article->getTranslation($locale);

        return response()->json($article);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'is_featured' => 'boolean',
            'featured_image' => 'nullable|string',
            
            // Translation data
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|max:10',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.subtitle' => 'nullable|string|max:255',
            'translations.*.excerpt' => 'required|string',
            'translations.*.content' => 'required|string',
            'translations.*.meta_title' => 'nullable|string|max:255',
            'translations.*.meta_description' => 'nullable|string',
            'translations.*.meta_keywords' => 'nullable|array',
        ]);

        // Generate slug from the first translation title
        $firstTranslation = collect($request->translations)->first();
        $slug = Str::slug($firstTranslation['title']);
        
        // Ensure unique slug
        $originalSlug = $slug;
        $counter = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $article = Article::create([
            'slug' => $slug,
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'status' => $request->status ?? 'draft',
            'is_featured' => $request->is_featured ?? false,
            'featured_image' => $request->featured_image,
            'published_at' => $request->status === 'published' ? now() : null,
        ]);

        // Create translations
        foreach ($request->translations as $translationData) {
            ArticleTranslation::create([
                'article_id' => $article->id,
                'locale' => $translationData['locale'],
                'title' => $translationData['title'],
                'subtitle' => $translationData['subtitle'] ?? null,
                'excerpt' => $translationData['excerpt'],
                'content' => $translationData['content'],
                'meta_title' => $translationData['meta_title'] ?? null,
                'meta_description' => $translationData['meta_description'] ?? null,
                'meta_keywords' => $translationData['meta_keywords'] ?? null,
                'slug_translation' => Str::slug($translationData['title']),
            ]);
        }

        $article->load(['translations', 'category', 'user']);

        return response()->json([
            'message' => 'Article created successfully',
            'article' => $article
        ], 201);
    }

    public function update(Request $request, Article $article)
    {
        // Check if user can edit this article
        if (!$request->user()->canManageContent() && $article->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'status' => 'sometimes|in:draft,published,archived',
            'is_featured' => 'boolean',
            'featured_image' => 'nullable|string',
        ]);

        $article->update($request->only([
            'category_id', 'status', 'is_featured', 'featured_image'
        ]));

        // Update published_at if status changed to published
        if ($request->status === 'published' && !$article->published_at) {
            $article->update(['published_at' => now()]);
        }

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => $article->load(['translations', 'category', 'user'])
        ]);
    }

    public function destroy(Request $request, Article $article)
    {
        // Only admin or article owner can delete
        if (!$request->user()->isAdmin() && $article->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }
}
