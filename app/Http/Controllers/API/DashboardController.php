<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get user dashboard overview data
     */
    public function overview(Request $request)
    {
        $user = $request->user();

        try {
            // Get user's investments
            $investments = Investment::where('user_id', $user->id)
                ->with('project')
                ->get();

            // Calculate portfolio statistics
            $totalInvested = $investments->sum('amount');
            $currentValue = $investments->sum('current_value');
            $totalReturns = $currentValue - $totalInvested;
            $returnPercentage = $totalInvested > 0 
                ? round(($totalReturns / $totalInvested) * 100, 2) 
                : 0;

            // Calculate monthly income
            $monthlyIncome = $investments->sum(function ($investment) {
                return $investment->monthly_income;
            });

            // Get active projects count
            $activeProjects = $investments->where('status', 'active')->count();

            // Get recent transactions (last 5)
            $recentTransactions = Transaction::where('user_id', $user->id)
                ->with(['project:id,name', 'investment'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'type' => $transaction->type,
                        'project' => $transaction->project ? $transaction->project->name : 'N/A',
                        'amount' => (float) $transaction->amount,
                        'date' => $transaction->created_at->format('Y-m-d'),
                        'status' => $transaction->status,
                        'description' => $transaction->description,
                    ];
                });

            // Get portfolio breakdown by project
            $portfolioData = $investments->map(function ($investment) {
                return [
                    'id' => $investment->id,
                    'project_id' => $investment->project_id,
                    'name' => $investment->project->name,
                    'type' => $investment->project->type,
                    'invested' => (float) $investment->amount,
                    'current_value' => (float) $investment->current_value,
                    'returns' => (float) $investment->return_percentage,
                    'status' => $investment->status,
                    'location' => $investment->project->location . ', ' . $investment->project->location_state,
                    'capacity' => (float) $investment->project->capacity . ' MW',
                    'monthly_income' => (float) $investment->monthly_income,
                    'completion' => $investment->project->completion_percentage,
                ];
            });

            // Get portfolio performance data (last 12 months)
            $performanceData = $this->getPortfolioPerformance($user->id);

            return response()->json([
                'stats' => [
                    'total_invested' => (float) $totalInvested,
                    'current_value' => (float) $currentValue,
                    'total_returns' => (float) $totalReturns,
                    'return_percentage' => (float) $returnPercentage,
                    'monthly_income' => (float) $monthlyIncome,
                    'active_projects' => $activeProjects,
                ],
                'portfolio' => $portfolioData,
                'recent_transactions' => $recentTransactions,
                'performance' => $performanceData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get portfolio performance data for the last 12 months
     */
    private function getPortfolioPerformance($userId)
    {
        $performance = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Get current month
        $currentMonth = now()->month;
        
        // Get investments for this user
        $investments = Investment::where('user_id', $userId)->get();
        $baseValue = $investments->sum('amount');

        // Generate mock performance data (in real app, this would come from historical data)
        foreach ($months as $index => $month) {
            if ($index <= $currentMonth - 1) {
                // Calculate progressive growth
                $monthlyGrowth = 1 + (rand(8, 15) / 100); // 8-15% monthly growth
                $value = $baseValue * pow($monthlyGrowth, $index + 1);
                
                $performance[] = [
                    'month' => $month,
                    'value' => round($value, 2),
                ];
            }
        }

        return $performance;
    }

    /**
     * Get user's investment portfolio
     */
    public function portfolio(Request $request)
    {
        $user = $request->user();

        try {
            $investments = Investment::where('user_id', $user->id)
                ->with('project')
                ->get()
                ->map(function ($investment) {
                    return [
                        'id' => $investment->id,
                        'project_id' => $investment->project_id,
                        'name' => $investment->project->name,
                        'type' => $investment->project->type,
                        'invested' => (float) $investment->amount,
                        'current_value' => (float) $investment->current_value,
                        'returns' => (float) $investment->return_percentage,
                        'status' => $investment->status,
                        'location' => $investment->project->location . ', ' . $investment->project->location_state,
                        'capacity' => (float) $investment->project->capacity . ' MW',
                        'monthly_income' => (float) $investment->monthly_income,
                        'completion' => $investment->project->completion_percentage,
                        'investment_date' => $investment->investment_date->format('Y-m-d'),
                    ];
                });

            return response()->json([
                'investments' => $investments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching portfolio data',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available investment projects
     */
    public function availableProjects(Request $request)
    {
        try {
            $projects = Project::whereIn('status', ['funding', 'active'])
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'slug' => $project->slug,
                        'type' => $project->type,
                        'description' => $project->description,
                        'location' => $project->location . ', ' . $project->location_state,
                        'capacity' => (float) $project->capacity . ' MW',
                        'funding_goal' => (float) $project->funding_goal,
                        'current_funding' => (float) $project->current_funding,
                        'funding_progress' => $project->funding_progress,
                        'expected_return' => (float) $project->expected_annual_return,
                        'minimum_investment' => (float) $project->minimum_investment,
                        'duration_months' => $project->duration_months,
                        'status' => $project->status,
                        'investors_count' => $project->investors_count,
                        'completion_percentage' => $project->completion_percentage,
                        'image_url' => $project->image_url,
                    ];
                });

            return response()->json([
                'projects' => $projects
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching projects',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}