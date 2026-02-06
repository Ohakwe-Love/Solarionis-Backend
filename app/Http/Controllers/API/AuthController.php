<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Send verification code to email
     */
    public function sendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'This email is already registered or invalid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate 6-digit verification code
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store temporarily in cache (expires in 15 minutes)
            cache()->put(
                'verification_code_' . $request->email,
                [
                    'code' => $verificationCode,
                    'expires_at' => Carbon::now()->addMinutes(15)
                ],
                900 // 15 minutes in seconds
            );

            // Log the code for development
            Log::info("Verification code for {$request->email}: {$verificationCode}");

            // Send email
            Mail::raw(
                "Your Solarionis verification code is: {$verificationCode}\n\nThis code will expire in 15 minutes.",
                function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Solarionis - Email Verification Code');
                }
            );

            return response()->json([
                'message' => 'Verification code sent successfully',
                'email' => $request->email,
                // For development only - remove in production
                'debug_code' => config('app.debug') ? $verificationCode : null
            ], 200);

        } catch (\Exception $e) {
            Log::error('Verification email error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send verification email. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Verify the code sent to email
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get cached verification data
            $cachedData = cache()->get('verification_code_' . $request->email);

            if (!$cachedData) {
                return response()->json([
                    'message' => 'Verification code has expired. Please request a new one.'
                ], 422);
            }

            // Check if code matches
            if ($cachedData['code'] !== $request->code) {
                return response()->json([
                    'message' => 'Invalid verification code'
                ], 422);
            }

            // Check if code has expired
            if (Carbon::now()->greaterThan($cachedData['expires_at'])) {
                cache()->forget('verification_code_' . $request->email);
                return response()->json([
                    'message' => 'Verification code has expired. Please request a new one.'
                ], 422);
            }

            // Mark email as verified in cache
            cache()->put('email_verified_' . $request->email, true, 1800); // 30 minutes

            return response()->json([
                'message' => 'Email verified successfully',
                'verified' => true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Verify code error: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred during verification',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Complete registration (only if email is verified)
     */
    public function register(Request $request)
    {
        try {
            // First, check if email was verified
            $isEmailVerified = cache()->get('email_verified_' . $request->email);

            if (!$isEmailVerified) {
                return response()->json([
                    'message' => 'Please verify your email first before completing registration'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'investment_type' => 'required|string|in:individual,business,non-accredited,ira,wealth-manager',
                'email' => 'required|email|unique:users,email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date|before:today',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'zip_code' => 'required|string|max:10',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create user
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'investment_type' => $request->investment_type,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'password' => Hash::make($request->password),
                'is_email_verified' => true,
                'email_verified_at' => Carbon::now(),
            ]);

            // Create token with abilities
            $token = $user->createToken('auth_token', ['*'])->plainTextToken;

            // Clear verification cache
            cache()->forget('verification_code_' . $request->email);
            cache()->forget('email_verified_' . $request->email);

            return response()->json([
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'investment_type' => $user->investment_type,
                ],
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create token with abilities
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'investment_type' => $user->investment_type,
            ],
            'token' => $token
        ], 200);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ], 200);
    }
}