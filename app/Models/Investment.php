<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'offering_id',
        'amount',
        'shares',
        'share_price',
        'status',
        'invested_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'shares' => 'decimal:4',
        'share_price' => 'decimal:2',
        'invested_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function distributions()
    {
        return $this->hasMany(Distribution::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // public function getCurrentValueAttribute()
    // {
    //     // Current value = shares * current share price
    //     // For now, we'll use a simple appreciation model
    //     // In production, you'd track actual market value

    //     $monthsHeld = now()->diffInMonths($this->invested_at);
    //     $annualReturn = $this->project->expected_annual_return / 100;
    //     $growthFactor = pow(1 + $annualReturn, $monthsHeld / 12);

    //     return round($this->amount * $growthFactor, 2);
    // }

    // public function getTotalReturnsAttribute()
    // {
    //     return round($this->current_value - $this->amount, 2);
    // }

    // public function getReturnPercentageAttribute()
    // {
    //     if ($this->amount == 0) return 0;
    //     return round(($this->total_returns / $this->amount) * 100, 2);
    // }

    // Investment.php

    public function getSimulatedCurrentValueAttribute()
    {
        $monthsHeld = now()->diffInMonths($this->invested_at);
        $annualReturn = $this->project->expected_annual_return / 100;

        $growthFactor = pow(1 + $annualReturn, $monthsHeld / 12);

        return round($this->amount * $growthFactor, 2);
    }

    public function getSimulatedTotalReturnsAttribute()
    {
        return round($this->simulated_current_value - $this->amount, 2);
    }

    public function getSimulatedReturnPercentageAttribute()
    {
        if ($this->amount == 0) return 0;

        return round(($this->simulated_total_returns / $this->amount) * 100, 2);
    }


    public function getMonthlyIncomeAttribute()
    {
        // Monthly income from distributions
        $paidDistributions = $this->distributions()
            ->paid()
            ->whereYear('period_start', now()->year)
            ->whereMonth('period_start', now()->month)
            ->sum('amount');

        return (float) $paidDistributions;
    }

    public function getExpectedMonthlyIncomeAttribute()
    {
        // Expected monthly income based on current value and project return
        $annualIncome = ($this->current_value * $this->project->expected_annual_return) / 100;
        return round($annualIncome / 12, 2);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
