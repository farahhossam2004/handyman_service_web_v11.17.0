<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EscrowTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'escrow_transactions';

    protected $fillable = [
        'escrowable_type',
        'escrowable_id',
        'customer_id',
        'provider_id',
        'payment_id',
        'amount',
        'held_amount',
        'released_amount',
        'refunded_amount',
        'penalty_deducted',
        'status',
        'held_at',
        'released_at',
        'refunded_at',
        'frozen_at',
        'scheduled_release_at',
        'notes',
        'actioned_by',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'held_amount'          => 'decimal:2',
        'released_amount'      => 'decimal:2',
        'refunded_amount'      => 'decimal:2',
        'penalty_deducted'     => 'decimal:2',
        'held_at'              => 'datetime',
        'released_at'          => 'datetime',
        'refunded_at'          => 'datetime',
        'frozen_at'            => 'datetime',
        'scheduled_release_at' => 'datetime',
    ];

    public function escrowable()
    {
        return $this->morphTo();
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['held', 'frozen_under_investigation']);
    }

    public function scopeScheduledForRelease($query)
    {
        return $query->where('status', 'held')
            ->whereNotNull('scheduled_release_at')
            ->where('scheduled_release_at', '<=', now());
    }
}
