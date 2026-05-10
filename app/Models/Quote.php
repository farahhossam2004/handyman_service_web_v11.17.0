<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Quote Model
 *
 * Represents a price quote submitted by a provider after inspecting a booking.
 *
 * Workflow:
 *  1. Booking created → status = pending_inspection
 *  2. Provider views booking → status = waiting_quote
 *  3. Provider submits quote → Quote created, booking status = quoted
 *  4. User approves → booking status = quote_approved
 *  5. User pays → booking status = in_progress, payment_status = held
 *  6. Service complete → booking status = completed, payment_status = released
 */
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quotes';

    protected $fillable = [
        'booking_id',
        'provider_id',
        'price',
        'notes',
        'status',
    ];

    protected $casts = [
        'booking_id'  => 'integer',
        'provider_id' => 'integer',
        'price'       => 'double',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id')->withTrashed();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
