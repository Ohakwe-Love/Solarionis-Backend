<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_id',
        'project_id',
        'type',
        'amount',
        'description',
        'status',
        'reference_number',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Auto-generate reference number if not provided
            if (!$transaction->reference_number) {
                $transaction->reference_number = 'TXN-' . strtoupper(Str::random(12));
            }

            // Set occurred_at to now if not provided
            if (!$transaction->occurred_at) {
                $transaction->occurred_at = now();
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('occurred_at', 'desc')->limit($limit);
    }

    public function isCredit(): bool
    {
        return in_array($this->type, ['deposit', 'dividend']);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['withdrawal', 'fee', 'investment']);
    }
}
