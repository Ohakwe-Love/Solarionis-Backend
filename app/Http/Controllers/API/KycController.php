<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\Request;

class KycController extends Controller
{
    /**
     * Get KYC status for authenticated user
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        $kyc = $user->kycVerification;

        if (!$kyc) {
            return response()->json([
                'status' => 'not_started',
                'is_verified' => false,
            ], 200);
        }

        return response()->json([
            'status' => $kyc->status,
            'is_verified' => $kyc->isVerified(),
            'provider' => $kyc->provider,
            'verified_at' => $kyc->verified_at,
            'failure_reason' => $kyc->failure_reason,
        ], 200);
    }

    /**
     * Start KYC process
     */
    public function start(Request $request)
    {
        $user = $request->user();

        // Check if KYC already exists
        $kyc = $user->kycVerification;

        if ($kyc && $kyc->isVerified()) {
            return response()->json([
                'message' => 'KYC already verified',
                'kyc' => [
                    'status' => $kyc->status,
                    'verified_at' => $kyc->verified_at,
                ]
            ], 200);
        }

        // Create or update KYC record
        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => 'manual', // Later: stripe, persona, etc.
                'status' => 'pending',
            ]
        );

        return response()->json([
            'message' => 'KYC verification started',
            'kyc' => [
                'status' => $kyc->status,
                'provider' => $kyc->provider,
            ]
        ], 201);
    }

    /**
     * Mock KYC verification (DEV ONLY)
     * In production, this would be replaced by webhook from KYC provider
     */
    public function mockVerify(Request $request)
    {
        // Only allow in non-production environments
        if (config('app.env') === 'production') {
            return response()->json([
                'message' => 'This endpoint is not available in production'
            ], 403);
        }

        $user = $request->user();
        $kyc = $user->kycVerification;

        if (!$kyc) {
            return response()->json([
                'message' => 'No KYC record found. Please start KYC first.'
            ], 404);
        }

        if ($kyc->isVerified()) {
            return response()->json([
                'message' => 'KYC already verified'
            ], 200);
        }

        $kyc->markAsVerified();

        return response()->json([
            'message' => 'KYC verified successfully (mock)',
            'kyc' => [
                'status' => $kyc->status,
                'verified_at' => $kyc->verified_at,
            ]
        ], 200);
    }
}