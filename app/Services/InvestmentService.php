<?php

namespace App\Services;

use App\Models\Investment;
use App\Models\Offering;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentService
{
    /**
     * Preview investment
     */
    public function previewInvestment(int $userId, int $offeringId, float $amount): array
    {
        $offering = Offering::with('project')->findOrFail($offeringId);

        [$shares, $projectedMonthlyIncome] = $this->calculatePreview($offering, $amount);

        return [
            'amount' => round($amount, 2),
            'shares' => $shares,
            'share_price' => (float) $offering->share_price,
            'project_name' => $offering->project->name,
            'expected_annual_return' => (float) $offering->project->expected_annual_return,
            'expected_monthly_income' => $projectedMonthlyIncome,
            'fees' => 0.00,
            'total' => round($amount, 2),
        ];
    }

    /**
     * Confirm investment (DB writes + locking + idempotency)
     */
    public function confirmInvestment(int $userId, int $offeringId, float $amount, ?string $idempotencyKey): Investment
    {
        if (!$idempotencyKey) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Missing Idempotency-Key header.'
            ]);
        }

        // KYC gating (service layer, not controller)
        /** @var User $user */
        $user = User::query()->findOrFail($userId);
        if (!$user->isKycVerified()) {
            throw ValidationException::withMessages([
                'kyc' => 'Please complete KYC verification before investing.'
            ]);
        }

        return DB::transaction(function () use ($userId, $offeringId, $amount, $idempotencyKey) {

            // If this request already succeeded, return the existing investment (idempotency).
            $existingTxn = Transaction::query()
                ->where('reference_number', $idempotencyKey)
                ->where('type', 'investment')
                ->first();

            if ($existingTxn?->investment) {
                return $existingTxn->investment;
            }

            // Lock offering row to prevent overselling
            $offering = Offering::with('project')
                ->whereKey($offeringId)
                ->lockForUpdate()
                ->firstOrFail();

            // Validate rules + compute shares
            [$shares] = $this->validateAndComputeShares($offering, $amount);

            // Create investment
            $investment = Investment::create([
                'user_id'     => $userId,
                'project_id'  => $offering->project_id,
                'offering_id' => $offering->id,
                'amount'      => round($amount, 2),
                'shares'      => $shares,
                'share_price' => (float) $offering->share_price,
                'status'      => 'active',
                'invested_at' => now(),
            ]);

            // Update offering/project atomically in same transaction
            $offering->increment('shares_sold', $shares);
            $offering->project->increment('current_funding', round($amount, 2));

            // Record transaction (audit)
            Transaction::create([
                'user_id'          => $userId,
                'investment_id'    => $investment->id,
                'project_id'       => $offering->project_id,
                'type'             => 'investment',
                'amount'           => round($amount, 2),
                'description'      => "Investment in {$offering->project->name}",
                'status'           => 'completed',
                'reference_number' => $idempotencyKey,
                'occurred_at'      => now(),
                'metadata'         => [
                    'offering_id' => $offering->id,
                    'shares'      => $shares,
                    'share_price' => (float) $offering->share_price,
                ],
            ]);

            return $investment;
        });
    }

    /**
     * Shared validation logic (single source of truth)
     */
    private function validateAndComputeShares(Offering $offering, float $amount): array
    {
        if (!$offering->isOpen()) {
            throw ValidationException::withMessages([
                'offering' => 'This offering is not currently active.'
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than 0.'
            ]);
        }

        if ($amount < (float) $offering->min_investment) {
            throw ValidationException::withMessages([
                'amount' => "Minimum investment is {$offering->min_investment}."
            ]);
        }

        $sharePrice = (float) $offering->share_price;
        if ($sharePrice <= 0) {
            throw ValidationException::withMessages([
                'offering' => 'Offering share price is invalid.'
            ]);
        }

        $shares = round($amount / $sharePrice, 4);

        // Check availability if capped
        if (!$offering->hasUnlimitedShares()) {
            $available = $offering->sharesAvailable();
            if ($available !== null && $shares > $available) {
                throw ValidationException::withMessages([
                    'amount' => "Not enough shares available. Available: {$available}."
                ]);
            }
        }

        return [$shares];
    }

    /**
     * Preview helper (no locking needed)
     */
    private function calculatePreview(Offering $offering, float $amount): array
    {
        [$shares] = $this->validateAndComputeShares($offering, $amount);

        $annualReturnPct = (float) $offering->project->expected_annual_return;
        $annualIncome = ($amount * $annualReturnPct) / 100;
        $monthlyIncome = round($annualIncome / 12, 2);

        return [$shares, $monthlyIncome];
    }
}
