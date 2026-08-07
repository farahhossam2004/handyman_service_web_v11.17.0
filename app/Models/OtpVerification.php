<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'phone',
        'purpose',
        'account_type',
        'msegat_request_id',
        'metadata',
        'attempts',
        'last_attempt_at',
        'expires_at',
        'cooldown_until',
        'verified_at',
    ];

    protected $casts = [
        'metadata'       => 'array',
        'attempts'       => 'integer',
        'last_attempt_at' => 'datetime',
        'expires_at'     => 'datetime',
        'cooldown_until' => 'datetime',
        'verified_at'    => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isInCooldown(): bool
    {
        return $this->cooldown_until !== null && $this->cooldown_until->isFuture();
    }

    public function maxAttemptsReached(): bool
    {
        return $this->attempts >= (int) config('services.msegat.otp_max_attempts', 5);
    }
}
