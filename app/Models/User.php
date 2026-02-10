<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'investment_type',
        'email',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'address',
        'city',
        'state',
        'zip_code',
        'password',
        'email_verified_at', // Using this instead of is_email_verified
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
    ];

    // Relationships
    public function kycVerifications()
    {
        return $this->hasMany(KycVerification::class);
    }

    public function currentKyc()
    {
        return $this->hasOne(KycVerification::class)->latestOfMany();
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

    // Helper methods
    public function isKycVerified()
    {
        return $this->currentKyc?->isVerified() ?? false;
    }

    public function hasEmailVerified()
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified()
    {
        $this->email_verified_at = now();
        $this->save();
    }

    // Accessor for backwards compatibility
    public function getIsEmailVerifiedAttribute()
    {
        return $this->hasEmailVerified();
    }
}
