<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Hash password using SHA256
        $hashedPassword = hash('sha256', $request->password);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $hashedPassword,
        ]);

        // Assign default 'user' role to all new registrations
        $userRole = \App\Models\Role::where('slug', 'user')->first();
        if ($userRole) {
            $user->roles()->attach($userRole->id);
        }

        $token = $user->createToken('oshongshoy-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user->load('roles'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Hash the provided password using SHA256
        $hashedPassword = hash('sha256', $request->password);
        
        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Verify password hash
        if (!$user || $user->password !== $hashedPassword) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        // Check if user is blocked
        if ($user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403);
        }

        $token = $user->createToken('oshongshoy-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load('roles'),
            'token' => $token,
            'unread_warnings' => $user->getUnreadWarningsCount(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles');
        return response()->json([
            'user' => $user,
            'unread_warnings' => $user->getUnreadWarningsCount(),
            'is_blocked' => $user->isBlocked(),
            'active_warnings' => $user->getActiveWarnings(),
        ]);
    }
}
