<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'location',
        'location_state',
        'capacity',
        'total_cost',
        'funding_goal',
        'current_funding',
        'expected_annual_return',
        'minimum_investment',
        'duration_months',
        'status',
        'completion_percentage',
        'funding_start_date',
        'funding_end_date',
        'project_start_date',
        'expected_completion_date',
        'image_url',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'funding_goal' => 'decimal:2',
        'current_funding' => 'decimal:2',
        'expected_annual_return' => 'decimal:2',
        'minimum_investment' => 'decimal:2',
        'funding_start_date' => 'date',
        'funding_end_date' => 'date',
        'project_start_date' => 'date',
        'expected_completion_date' => 'date',
    ];

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Get funding progress percentage
    public function getFundingProgressAttribute()
    {
        if ($this->funding_goal == 0) return 0;
        return round(($this->current_funding / $this->funding_goal) * 100, 2);
    }

    // Get number of investors
    public function getInvestorsCountAttribute()
    {
        return $this->investments()->distinct('user_id')->count('user_id');
    }
}