<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'share_price',
        'min_investment',
        'opens_at',
        'closes_at',
        'status',
        'total_shares',
        'shares_sold',
    ];

    protected $casts = [
        'share_price' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    // Computed attributes
    public function getSharesAvailableAttribute()
    {
        if ($this->total_shares === null) {
            return null; // Unlimited
        }
        return $this->total_shares - $this->shares_sold;
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'open'
            && ($this->opens_at === null || $this->opens_at <= now())
            && ($this->closes_at === null || $this->closes_at >= now())
            && ($this->total_shares === null || $this->shares_sold < $this->total_shares);
    }

    public function getFundingProgressAttribute()
    {
        if ($this->total_shares === null) {
            return 0;
        }
        return round(($this->shares_sold / $this->total_shares) * 100, 2);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('opens_at')
                    ->orWhere('opens_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('closes_at')
                    ->orWhere('closes_at', '>=', now());
            });
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function hasUnlimitedShares(): bool
    {
        return is_null($this->total_shares);
    }

    public function sharesAvailable(): ?float
    {
        if ($this->hasUnlimitedShares()) {
            return null;
        }

        return max(0, $this->total_shares - $this->shares_sold);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open'
            && ($this->opens_at === null || $this->opens_at <= now())
            && ($this->closes_at === null || $this->closes_at >= now());
    }
}
