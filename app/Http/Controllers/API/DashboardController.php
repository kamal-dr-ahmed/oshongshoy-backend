<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Models\Category;
use App\Models\ModerationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        try {
            $user = $request->user()->load('roles');
            
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return $this->adminStats($user);
            } elseif ($user->isModerator()) {
                return $this->moderatorStats($user);
            } else {
                return $this->userStats($user);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch stats'], 500);
        }
    }

    private function adminStats($user)
    {
        $stats = [
            'total_users' => User::count(),
            'total_articles' => Article::count(),
            'pending_articles' => Article::where('status', 'pending')->count(),
            'approved_articles' => Article::where('status', 'approved')->count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'rejected_articles' => Article::where('status', 'rejected')->count(),
            'total_categories' => Category::count(),
            'active_moderators' => User::whereHas('roles', function($q) {
                $q->whereIn('slug', ['moderator', 'admin', 'superadmin']);
            })->count(),
            'blocked_users' => User::whereHas('blocks', function($q) {
                $q->where('is_active', true);
            })->count(),
            'recent_activities' => ModerationLog::with(['moderator:id,name', 'article.translations'])
                ->orderBy('created_at', 'desc')->limit(10)->get(),
            'articles_by_status' => Article::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')->get(),
            'recent_users' => User::with('roles')->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
        return response()->json(['success' => true, 'stats' => $stats, 'role' => 'admin']);
    }

    private function moderatorStats($user)
    {
        $stats = [
            'pending_articles' => Article::where('status', 'pending')->count(),
            'approved_articles' => Article::where('status', 'approved')->count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'my_moderation_count' => ModerationLog::where('moderator_id', $user->id)->count(),
            'pending_articles_list' => Article::where('status', 'pending')
                ->with(['user:id,name,email', 'category:id,name', 'translations'])
                ->orderBy('submitted_at', 'asc')->limit(20)->get(),
            'recent_moderation' => ModerationLog::where('moderator_id', $user->id)
                ->with(['article.translations', 'article.user:id,name'])
                ->orderBy('created_at', 'desc')->limit(10)->get(),
        ];
        return response()->json(['success' => true, 'stats' => $stats, 'role' => 'moderator']);
    }

    private function userStats($user)
    {
        try {
            $stats = [
                'total_articles' => $user->articles()->count(),
                'draft_articles' => $user->articles()->where('status', 'draft')->count(),
                'pending_articles' => $user->articles()->where('status', 'pending')->count(),
                'approved_articles' => $user->articles()->where('status', 'approved')->count(),
                'published_articles' => $user->articles()->where('status', 'published')->count(),
                'rejected_articles' => $user->articles()->where('status', 'rejected')->count(),
                'total_views' => $user->articles()->sum('view_count'),
                'recent_articles' => $user->articles()
                    ->with(['category:id,name_bn,name_en', 'translations'])
                    ->orderBy('updated_at', 'desc')->limit(10)->get(),
                'warnings' => [], // Temporarily simplified
                'unread_warnings_count' => 0, // Temporarily simplified
            ];
            return response()->json(['success' => true, 'stats' => $stats, 'role' => 'user']);
        } catch (\Exception $e) {
            \Log::error('Dashboard userStats error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function users(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            $users = User::with('roles', 'blocks')
                ->when($search, function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc')->paginate($perPage);
            return response()->json(['success' => true, 'users' => $users]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch users'], 500);
        }
    }

    public function articles(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status', '');
            $search = $request->get('search', '');
            $articles = Article::with(['user:id,name,email', 'category:id,name', 'translations', 'moderator:id,name'])
                ->when($status, function($q) use ($status) {
                    $q->where('status', $status);
                })
                ->when($search, function($q) use ($search) {
                    $q->whereHas('translations', function($query) use ($search) {
                        $query->where('title', 'like', "%{$search}%");
                    });
                })
                ->orderBy('created_at', 'desc')->paginate($perPage);
            return response()->json(['success' => true, 'articles' => $articles]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch articles'], 500);
        }
    }
}
