<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\UserBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    public function blockUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string',
                'block_type' => 'required|in:temporary,permanent',
                'duration_days' => 'required_if:block_type,temporary|nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($id);
            
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Cannot block admin users'], 403);
            }

            $expiresAt = $request->block_type === 'temporary' 
                ? now()->addDays($request->duration_days) 
                : null;

            UserBlock::create([
                'user_id' => $user->id,
                'blocked_by' => $request->user()->id,
                'block_type' => $request->block_type,
                'reason' => $request->reason,
                'blocked_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);

            $user->tokens()->delete();

            return response()->json(['success' => true, 'message' => 'User blocked successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to block user'], 500);
        }
    }

    public function unblockUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($id);
            $activeBlock = $user->blocks()->where('is_active', true)->first();

            if (!$activeBlock) {
                return response()->json(['success' => false, 'message' => 'User is not blocked'], 400);
            }

            $activeBlock->update([
                'is_active' => false,
                'unblock_reason' => $request->reason,
                'unblocked_by' => $request->user()->id,
                'unblocked_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'User unblocked successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to unblock user'], 500);
        }
    }

    public function warnUser(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'reason' => 'required|string',
                'severity' => 'required|in:low,medium,high,critical',
                'expires_in_days' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($id);
            $expiresAt = $request->expires_in_days ? now()->addDays($request->expires_in_days) : null;

            UserWarning::create([
                'user_id' => $user->id,
                'issued_by' => $request->user()->id,
                'severity' => $request->severity,
                'title' => $request->title,
                'reason' => $request->reason,
                'expires_at' => $expiresAt,
            ]);

            return response()->json(['success' => true, 'message' => 'Warning issued successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to issue warning'], 500);
        }
    }

    public function getUserWarnings(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $warnings = $user->warnings()->with('issuer:id,name,email')->orderBy('created_at', 'desc')->paginate(10);
            return response()->json(['success' => true, 'warnings' => $warnings]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch warnings'], 500);
        }
    }

    public function markWarningAsRead(Request $request, $warningId)
    {
        try {
            $warning = UserWarning::findOrFail($warningId);
            if ($warning->user_id !== $request->user()->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $warning->update(['is_read' => true]);
            return response()->json(['success' => true, 'message' => 'Warning marked as read']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update warning'], 500);
        }
    }

    public function assignRole(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_slug' => 'required|string|exists:roles,slug',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($id);
            $role = \App\Models\Role::where('slug', $request->role_slug)->first();

            if ($user->roles()->where('id', $role->id)->exists()) {
                return response()->json(['success' => false, 'message' => 'User already has this role'], 400);
            }

            $user->roles()->attach($role->id);
            return response()->json(['success' => true, 'message' => 'Role assigned successfully', 'user' => $user->load('roles')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to assign role'], 500);
        }
    }

    public function removeRole(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role_slug' => 'required|string|exists:roles,slug',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($id);
            $role = \App\Models\Role::where('slug', $request->role_slug)->first();

            $user->roles()->detach($role->id);
            return response()->json(['success' => true, 'message' => 'Role removed successfully', 'user' => $user->load('roles')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to remove role'], 500);
        }
    }
}
