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
        'amount',
        'shares',
        'share_price',
        'current_value',
        'total_returns',
        'return_percentage',
        'status',
        'investment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'shares' => 'decimal:2',
        'share_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'total_returns' => 'decimal:2',
        'return_percentage' => 'decimal:2',
        'investment_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Calculate monthly income based on annual return
    public function getMonthlyIncomeAttribute()
    {
        $annualReturn = ($this->current_value * $this->project->expected_annual_return) / 100;
        return round($annualReturn / 12, 2);
    }
}