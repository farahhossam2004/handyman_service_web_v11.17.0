<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotes';

    protected $fillable = [
        'booking_id',
        'provider_id',
        'handyman_id',
        'price',
        'estimated_duration',
        'notes',
        'inspection_notes',
        'status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'booking_id'        => 'integer',
        'provider_id'       => 'integer',
        'handyman_id'       => 'integer',
        'price'             => 'double',
        'estimated_duration'=> 'integer',
        'approved_at'       => 'datetime',
        'rejected_at'       => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id')->withTrashed();
    }

    public function handyman()
    {
        return $this->belongsTo(User::class, 'handyman_id', 'id')->withTrashed();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
}
