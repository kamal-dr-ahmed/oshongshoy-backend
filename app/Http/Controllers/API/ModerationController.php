<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModerationController extends Controller
{
    public function pending(Request $request)
    {
        try {
            $articles = Article::where('status', 'pending')
                ->with(['user', 'category', 'translations'])
                ->orderBy('submitted_at', 'asc')
                ->paginate(20);
            return response()->json(['success' => true, 'articles' => $articles]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch pending articles'], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment' => 'nullable|string',
                'publish_immediately' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $article = Article::findOrFail($id);
            $previousStatus = $article->status;
            $newStatus = $request->publish_immediately ? 'published' : 'approved';

            $article->update([
                'status' => $newStatus,
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'moderation_notes' => $request->comment,
                'published_at' => $request->publish_immediately ? now() : $article->published_at,
            ]);

            ModerationLog::create([
                'article_id' => $article->id,
                'moderator_id' => $request->user()->id,
                'action' => $request->publish_immediately ? 'published' : 'approved',
                'comment' => $request->comment,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]);

            return response()->json(['success' => true, 'message' => 'Article approved successfully', 'article' => $article->fresh(['translations', 'category', 'user'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to approve article'], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $article = Article::findOrFail($id);
            $previousStatus = $article->status;

            $article->update([
                'status' => 'rejected',
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'moderation_notes' => $request->reason,
            ]);

            ModerationLog::create([
                'article_id' => $article->id,
                'moderator_id' => $request->user()->id,
                'action' => 'rejected',
                'comment' => $request->reason,
                'previous_status' => $previousStatus,
                'new_status' => 'rejected',
            ]);

            return response()->json(['success' => true, 'message' => 'Article rejected', 'article' => $article->fresh(['translations', 'category', 'user'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reject article'], 500);
        }
    }

    public function requestChanges(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'feedback' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $article = Article::findOrFail($id);
            $previousStatus = $article->status;

            $article->update([
                'status' => 'changes_requested',
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'moderation_notes' => $request->feedback,
            ]);

            ModerationLog::create([
                'article_id' => $article->id,
                'moderator_id' => $request->user()->id,
                'action' => 'changes_requested',
                'comment' => $request->feedback,
                'previous_status' => $previousStatus,
                'new_status' => 'changes_requested',
            ]);

            return response()->json(['success' => true, 'message' => 'Changes requested', 'article' => $article->fresh(['translations', 'category', 'user'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to request changes'], 500);
        }
    }

    public function publish(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);
            
            if ($article->status !== 'approved') {
                return response()->json(['success' => false, 'message' => 'Article must be approved first'], 400);
            }

            $previousStatus = $article->status;
            $article->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            ModerationLog::create([
                'article_id' => $article->id,
                'moderator_id' => $request->user()->id,
                'action' => 'published',
                'previous_status' => $previousStatus,
                'new_status' => 'published',
            ]);

            return response()->json(['success' => true, 'message' => 'Article published', 'article' => $article->fresh(['translations', 'category'])]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to publish article'], 500);
        }
    }

    public function unpublish(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $article = Article::findOrFail($id);
            $previousStatus = $article->status;

            $article->update([
                'status' => 'approved',
                'moderation_notes' => $request->reason,
            ]);

            ModerationLog::create([
                'article_id' => $article->id,
                'moderator_id' => $request->user()->id,
                'action' => 'unpublished',
                'comment' => $request->reason,
                'previous_status' => $previousStatus,
                'new_status' => 'approved',
            ]);

            return response()->json(['success' => true, 'message' => 'Article unpublished']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to unpublish article'], 500);
        }
    }
}
