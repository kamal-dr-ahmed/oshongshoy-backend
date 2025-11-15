<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $articles = $request->user()->articles()
                ->with(['category', 'translations', 'tags', 'externalLinks', 'moderator'])
                ->orderBy('updated_at', 'desc')
                ->paginate(15);
            return response()->json(['success' => true, 'articles' => $articles]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch articles'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Decode JSON strings from FormData
            if ($request->has('tags') && is_string($request->tags)) {
                $request->merge(['tags' => json_decode($request->tags, true)]);
            }
            if ($request->has('external_links') && is_string($request->external_links)) {
                $request->merge(['external_links' => json_decode($request->external_links, true)]);
            }
            if ($request->has('meta_keywords') && is_string($request->meta_keywords)) {
                $request->merge(['meta_keywords' => json_decode($request->meta_keywords, true)]);
            }
            
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'locale' => 'required|string|in:bn,en,as,gu,hi,mr,ne,or,pa,si',
                'category_id' => 'required|exists:categories,id',
                'excerpt' => 'nullable|string',
                'featured_image' => 'nullable|string',
                'tags' => 'nullable|array',
                'reading_time' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $article = Article::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'slug' => Str::slug($request->title) . '-' . Str::random(8),
                'status' => 'draft',
                'reading_time' => $request->reading_time ?? 5,
                'featured_image' => $request->featured_image,
            ]);

            $translationData = [
                'article_id' => $article->id,
                'locale' => $request->locale,
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt ?? Str::limit(strip_tags($request->content), 200),
            ];
            
            // Add optional fields if provided
            if ($request->has('subtitle')) {
                $translationData['subtitle'] = $request->subtitle;
            }
            if ($request->has('meta_title')) {
                $translationData['meta_title'] = $request->meta_title;
            }
            if ($request->has('meta_description')) {
                $translationData['meta_description'] = $request->meta_description;
            }
            if ($request->has('meta_keywords')) {
                $translationData['meta_keywords'] = $request->meta_keywords;
            }
            
            ArticleTranslation::create($translationData);

            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    // Remove # if present
                    $tagName = ltrim($tagName, '#');
                    if (!empty($tagName)) {
                        $slug = \Str::slug($tagName);
                        // Find or create tag - use name_bn for Bengali tags
                        $tag = \App\Models\Tag::where('slug', $slug)->first();
                        
                        if (!$tag) {
                            $tag = \App\Models\Tag::create([
                                'name_bn' => $tagName,
                                'name_en' => $tagName, // Same for both for now
                                'slug' => $slug
                            ]);
                        }
                        $tagIds[] = $tag->id;
                    }
                }
                $article->tags()->sync($tagIds);
            }

            // Handle external links
            if ($request->has('external_links') && is_array($request->external_links)) {
                foreach ($request->external_links as $linkData) {
                    if (is_array($linkData) && isset($linkData['url']) && isset($linkData['title'])) {
                        // Create or find the external link
                        $externalLink = \App\Models\ExternalLink::firstOrCreate(
                            ['url' => $linkData['url']],
                            [
                                'title' => $linkData['title'],
                                'type' => $linkData['type'] ?? 'reference'
                            ]
                        );
                        
                        // Attach to article
                        $article->externalLinks()->attach($externalLink->id);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Article saved as draft', 'article' => $article->load(['translations', 'category', 'tags', 'externalLinks'])], 201);
        } catch (\Exception $e) {
            \Log::error('Content create error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to create article', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $article = Article::with(['translations', 'category', 'tags', 'externalLinks', 'moderator', 'moderationLogs.moderator'])->findOrFail($id);
            if ($article->user_id !== $request->user()->id && !$request->user()->canModerate()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            return response()->json(['success' => true, 'article' => $article]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Article not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);
            if ($article->user_id !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            if (!in_array($article->status, ['draft', 'rejected', 'changes_requested'])) {
                return response()->json(['success' => false, 'message' => 'Cannot edit article in current status'], 400);
            }

            // Decode JSON strings from FormData
            if ($request->has('tags') && is_string($request->tags)) {
                $request->merge(['tags' => json_decode($request->tags, true)]);
            }
            if ($request->has('external_links') && is_string($request->external_links)) {
                $request->merge(['external_links' => json_decode($request->external_links, true)]);
            }
            if ($request->has('meta_keywords') && is_string($request->meta_keywords)) {
                $request->merge(['meta_keywords' => json_decode($request->meta_keywords, true)]);
            }

            // Get only the fields we want to validate (exclude _method and other meta fields)
            $dataToValidate = $request->except(['_method', '_token']);
            
            $validator = Validator::make($dataToValidate, [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'locale' => 'sometimes|required|string|in:bn,en,as,gu,hi,mr,ne,or,pa,si',
                'category_id' => 'sometimes|required|exists:categories,id',
                'excerpt' => 'nullable|string',
                'featured_image' => 'nullable|string',
                'tags' => 'nullable|array',
                'reading_time' => 'nullable|integer',
                'subtitle' => 'nullable|string',
                'external_links' => 'nullable|array',
                'meta_title' => 'nullable|string',
                'meta_description' => 'nullable|string',
                'meta_keywords' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'received_data' => array_keys($request->all()) // Debug: show what fields were sent
                ], 422);
            }

            $article->update([
                'category_id' => $request->category_id ?? $article->category_id,
                'reading_time' => $request->reading_time ?? $article->reading_time,
                'featured_image' => $request->featured_image ?? $article->featured_image,
                'revision_count' => $article->revision_count + 1,
            ]);

            if ($request->has('title') || $request->has('content')) {
                $locale = $request->locale ?? 'bn';
                $translation = $article->translations()->where('locale', $locale)->first();
                
                $translationData = [
                    'title' => $request->title ?? ($translation ? $translation->title : ''),
                    'content' => $request->content ?? ($translation ? $translation->content : ''),
                    'excerpt' => $request->excerpt ?? ($translation ? $translation->excerpt : Str::limit(strip_tags($request->content ?? ''), 200)),
                ];
                
                // Add meta fields if provided
                if ($request->has('subtitle')) {
                    $translationData['subtitle'] = $request->subtitle;
                }
                if ($request->has('meta_title')) {
                    $translationData['meta_title'] = $request->meta_title;
                }
                if ($request->has('meta_description')) {
                    $translationData['meta_description'] = $request->meta_description;
                }
                if ($request->has('meta_keywords')) {
                    $translationData['meta_keywords'] = $request->meta_keywords;
                }
                
                if ($translation) {
                    $translation->update($translationData);
                } else {
                    $translationData['article_id'] = $article->id;
                    $translationData['locale'] = $locale;
                    ArticleTranslation::create($translationData);
                }
            }

            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    // Remove # if present
                    $tagName = ltrim($tagName, '#');
                    if (!empty($tagName)) {
                        $slug = \Str::slug($tagName);
                        // Find or create tag - use name_bn for Bengali tags
                        $tag = \App\Models\Tag::where('slug', $slug)->first();
                        
                        if (!$tag) {
                            $tag = \App\Models\Tag::create([
                                'name_bn' => $tagName,
                                'name_en' => $tagName, // Same for both for now
                                'slug' => $slug
                            ]);
                        }
                        $tagIds[] = $tag->id;
                    }
                }
                $article->tags()->sync($tagIds);
            }

            // Handle external links
            if ($request->has('external_links') && is_array($request->external_links)) {
                // Detach all existing links first
                $article->externalLinks()->detach();
                
                foreach ($request->external_links as $linkData) {
                    if (is_array($linkData) && isset($linkData['url']) && isset($linkData['title'])) {
                        // Create or find the external link
                        $externalLink = \App\Models\ExternalLink::firstOrCreate(
                            ['url' => $linkData['url']],
                            [
                                'title' => $linkData['title'],
                                'type' => $linkData['type'] ?? 'reference'
                            ]
                        );
                        
                        // Attach to article
                        $article->externalLinks()->attach($externalLink->id);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Article updated', 'article' => $article->fresh(['translations', 'category', 'tags', 'externalLinks'])]);
        } catch (\Exception $e) {
            \Log::error('Content update error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update article: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function submit(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);
            if ($article->user_id !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            if (!in_array($article->status, ['draft', 'rejected', 'changes_requested'])) {
                return response()->json(['success' => false, 'message' => 'Article already submitted or published'], 400);
            }
            if (!$article->translations()->exists()) {
                return response()->json(['success' => false, 'message' => 'Article must have content'], 400);
            }

            $article->update(['status' => 'pending', 'submitted_at' => now()]);
            return response()->json(['success' => true, 'message' => 'Article submitted for review', 'article' => $article->fresh(['translations', 'category'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to submit article'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);
            if ($article->user_id !== $request->user()->id && !$request->user()->canModerate()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $article->delete();
            return response()->json(['success' => true, 'message' => 'Article deleted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete article'], 500);
        }
    }
}
