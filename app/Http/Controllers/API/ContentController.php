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
                ->with(['category', 'translations', 'moderator'])
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

            ArticleTranslation::create([
                'article_id' => $article->id,
                'locale' => $request->locale,
                'title' => $request->title,
                'content' => $request->content,
                'excerpt' => $request->excerpt ?? Str::limit(strip_tags($request->content), 200),
            ]);

            if ($request->has('tags') && is_array($request->tags)) {
                $article->tags()->sync($request->tags);
            }

            return response()->json(['success' => true, 'message' => 'Article saved as draft', 'article' => $article->load(['translations', 'category', 'tags'])], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create article', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $article = Article::with(['translations', 'category', 'tags', 'moderator', 'moderationLogs.moderator'])->findOrFail($id);
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
                if ($translation) {
                    $translation->update([
                        'title' => $request->title ?? $translation->title,
                        'content' => $request->content ?? $translation->content,
                        'excerpt' => $request->excerpt ?? $translation->excerpt,
                    ]);
                } else {
                    ArticleTranslation::create([
                        'article_id' => $article->id,
                        'locale' => $locale,
                        'title' => $request->title,
                        'content' => $request->content,
                        'excerpt' => $request->excerpt ?? Str::limit(strip_tags($request->content), 200),
                    ]);
                }
            }

            if ($request->has('tags')) {
                $article->tags()->sync($request->tags);
            }

            return response()->json(['success' => true, 'message' => 'Article updated', 'article' => $article->fresh(['translations', 'category', 'tags'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update article'], 500);
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
