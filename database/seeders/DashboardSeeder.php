<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample projects
        $projects = [
            [
                'name' => 'Solar Farm Alpha',
                'slug' => 'solar-farm-alpha',
                'type' => 'solar',
                'description' => 'Large-scale solar farm generating clean energy for over 10,000 homes.',
                'location' => 'Bakersfield',
                'location_state' => 'California',
                'capacity' => 5.2,
                'total_cost' => 5000000,
                'funding_goal' => 4000000,
                'current_funding' => 3200000,
                'expected_annual_return' => 12.5,
                'minimum_investment' => 100,
                'duration_months' => 120,
                'status' => 'active',
                'completion_percentage' => 85,
                'funding_start_date' => now()->subMonths(6),
                'funding_end_date' => now()->addMonths(2),
                'project_start_date' => now()->subMonths(4),
                'expected_completion_date' => now()->addMonths(8),
            ],
            [
                'name' => 'Community Solar Grid',
                'slug' => 'community-solar-grid',
                'type' => 'solar',
                'description' => 'Community-owned solar installation providing renewable energy to local neighborhoods.',
                'location' => 'Austin',
                'location_state' => 'Texas',
                'capacity' => 8.5,
                'total_cost' => 8000000,
                'funding_goal' => 6500000,
                'current_funding' => 5200000,
                'expected_annual_return' => 14.0,
                'minimum_investment' => 250,
                'duration_months' => 144,
                'status' => 'active',
                'completion_percentage' => 92,
                'funding_start_date' => now()->subMonths(8),
                'funding_end_date' => now()->addMonths(1),
                'project_start_date' => now()->subMonths(5),
                'expected_completion_date' => now()->addMonths(4),
            ],
            [
                'name' => 'Residential Solar Project',
                'slug' => 'residential-solar-project',
                'type' => 'solar',
                'description' => 'Rooftop solar installations across 500 residential properties.',
                'location' => 'Phoenix',
                'location_state' => 'Arizona',
                'capacity' => 3.8,
                'total_cost' => 3500000,
                'funding_goal' => 2800000,
                'current_funding' => 2100000,
                'expected_annual_return' => 11.5,
                'minimum_investment' => 100,
                'duration_months' => 96,
                'status' => 'active',
                'completion_percentage' => 78,
                'funding_start_date' => now()->subMonths(4),
                'funding_end_date' => now()->addMonths(3),
                'project_start_date' => now()->subMonths(2),
                'expected_completion_date' => now()->addMonths(10),
            ],
            [
                'name' => 'Industrial Solar Installation',
                'slug' => 'industrial-solar-installation',
                'type' => 'solar',
                'description' => 'Solar power system for manufacturing facilities and warehouses.',
                'location' => 'Las Vegas',
                'location_state' => 'Nevada',
                'capacity' => 2.1,
                'total_cost' => 2000000,
                'funding_goal' => 1500000,
                'current_funding' => 1500000,
                'expected_annual_return' => 15.0,
                'minimum_investment' => 500,
                'duration_months' => 84,
                'status' => 'completed',
                'completion_percentage' => 100,
                'funding_start_date' => now()->subMonths(12),
                'funding_end_date' => now()->subMonths(8),
                'project_start_date' => now()->subMonths(7),
                'expected_completion_date' => now()->subMonths(1),
            ],
            [
                'name' => 'Wind Farm Montana',
                'slug' => 'wind-farm-montana',
                'type' => 'wind',
                'description' => 'Modern wind turbine farm harnessing Montana\'s consistent winds.',
                'location' => 'Great Falls',
                'location_state' => 'Montana',
                'capacity' => 12.0,
                'total_cost' => 12000000,
                'funding_goal' => 10000000,
                'current_funding' => 4500000,
                'expected_annual_return' => 13.0,
                'minimum_investment' => 200,
                'duration_months' => 180,
                'status' => 'funding',
                'completion_percentage' => 35,
                'funding_start_date' => now()->subMonths(2),
                'funding_end_date' => now()->addMonths(6),
                'project_start_date' => null,
                'expected_completion_date' => now()->addMonths(24),
            ],
        ];

        foreach ($projects as $projectData) {
            Project::create($projectData);
        }

        // Get the first user (or create one for testing)
        $user = User::first();
        
        if ($user) {
            // Create sample investments for the user
            $projectsToInvest = Project::whereIn('slug', [
                'solar-farm-alpha',
                'community-solar-grid',
                'residential-solar-project',
                'industrial-solar-installation'
            ])->get();

            foreach ($projectsToInvest as $index => $project) {
                $investmentAmounts = [15000, 20000, 10000, 5000];
                $amount = $investmentAmounts[$index];
                
                // Calculate shares (simplified: $100 per share)
                $sharePrice = 100;
                $shares = $amount / $sharePrice;
                
                // Calculate current value with some growth
                $growthRate = 1 + ($project->expected_annual_return / 100);
                $monthsHeld = rand(3, 12);
                $currentValue = $amount * pow($growthRate, $monthsHeld / 12);
                $totalReturns = $currentValue - $amount;
                $returnPercentage = ($totalReturns / $amount) * 100;

                $investment = Investment::create([
                    'user_id' => $user->id,
                    'project_id' => $project->id,
                    'amount' => $amount,
                    'shares' => $shares,
                    'share_price' => $sharePrice,
                    'current_value' => $currentValue,
                    'total_returns' => $totalReturns,
                    'return_percentage' => $returnPercentage,
                    'status' => $project->status === 'completed' ? 'completed' : 'active',
                    'investment_date' => now()->subMonths($monthsHeld),
                ]);

                // Create investment transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'investment_id' => $investment->id,
                    'project_id' => $project->id,
                    'type' => 'investment',
                    'amount' => $amount,
                    'description' => "Investment in {$project->name}",
                    'status' => 'completed',
                    'reference_number' => 'TXN-' . strtoupper(Str::random(10)),
                ]);

                // Create some dividend transactions
                for ($i = 0; $i < rand(2, 5); $i++) {
                    $dividendAmount = ($currentValue * $project->expected_annual_return / 100) / 12;
                    
                    Transaction::create([
                        'user_id' => $user->id,
                        'investment_id' => $investment->id,
                        'project_id' => $project->id,
                        'type' => 'dividend',
                        'amount' => $dividendAmount,
                        'description' => "Monthly dividend from {$project->name}",
                        'status' => 'completed',
                        'reference_number' => 'TXN-' . strtoupper(Str::random(10)),
                    ]);
                }
            }

            $this->command->info('Sample dashboard data created successfully!');
        } else {
            $this->command->warn('No user found. Please create a user first.');
        }
    }
}