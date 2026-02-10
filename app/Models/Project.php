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
        'duration_months',
        'status',
        'completion_percentage',
        'project_start_date',
        'expected_completion_date',
        'image_url',
        'highlights',
        'documents',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'funding_goal' => 'decimal:2',
        'current_funding' => 'decimal:2',
        'expected_annual_return' => 'decimal:2',
        'project_start_date' => 'date',
        'expected_completion_date' => 'date',
        'highlights' => 'array',
        'documents' => 'array',
    ];

    // Relationships
    public function offerings()
    {
        return $this->hasMany(Offering::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function distributions()
    {
        return $this->hasMany(Distribution::class);
    }

    // Computed attributes
    public function getFundingProgressAttribute()
    {
        if ($this->funding_goal == 0) return 0;
        return round(($this->current_funding / $this->funding_goal) * 100, 2);
    }

    public function getInvestorsCountAttribute()
    {
        return $this->investments()->distinct('user_id')->count('user_id');
    }

    // public function getActiveOfferingAttribute()
    // {
    //     return $this->offerings()->active()->first();
    // }

    public function activeOffering()
    {
        return $this->hasOne(Offering::class)
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('opens_at')->orWhere('opens_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>=', now());
            });
    }

    // Scopes
    public function scopeFunding($query)
    {
        return $query->where('status', 'funding');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
