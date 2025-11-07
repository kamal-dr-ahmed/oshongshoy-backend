<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\OtpEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'current_password' => 'sometimes|required_with:new_password|string',
            'new_password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update name if provided
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        // Update password if provided
        if ($request->has('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function requestEmailChangeOTP(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'new_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes with the new email
        cache([
            "email_change_otp_{$user->id}" => [
                'otp' => $otp,
                'new_email' => $request->new_email,
            ]
        ], now()->addMinutes(10));

        // Send OTP to new email
        try {
            Mail::to($request->new_email)->send(new OtpEmail($otp, $user->name));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP email'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your new email address',
            'expires_in' => 600 // 10 minutes in seconds
        ]);
    }

    public function verifyEmailChangeOTP(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Retrieve cached OTP and new email
        $cachedData = cache("email_change_otp_{$user->id}");

        if (!$cachedData) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired or not found'
            ], 400);
        }

        if ($cachedData['otp'] !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        // Update email
        $user->email = $cachedData['new_email'];
        $user->email_verified_at = now();
        $user->save();

        // Clear the OTP cache
        cache()->forget("email_change_otp_{$user->id}");

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
            'user' => $user->load('roles')
        ]);
    }
}
