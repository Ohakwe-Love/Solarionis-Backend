<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvestmentPreviewRequest;
use App\Http\Requests\InvestmentConfirmRequest;
use App\Services\InvestmentService;
use Illuminate\Http\JsonResponse;

class InvestmentController extends Controller
{
    public function preview(
        InvestmentPreviewRequest $request,
        InvestmentService $service
    ): JsonResponse {
        $preview = $service->previewInvestment(
            $request->user()->id,
            $request->offering_id,
            $request->amount
        );

        return response()->json(['preview' => $preview]);
    }

    public function confirm(
        InvestmentConfirmRequest $request,
        InvestmentService $service
    ): JsonResponse {
        $investment = $service->confirmInvestment(
            userId: $request->user()->id,
            offeringId: $request->offering_id,
            amount: $request->amount,
            idempotencyKey: $request->header('Idempotency-Key')
        );

        return response()->json([
            'message' => 'Investment successful',
            'investment' => $investment,
        ], 201);
    }
}