<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestigationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'investigation_logs';

    protected $fillable = [
        'booking_id',
        'opened_by',
        'dispute_reason',
        'status',
        'resolution',
        'penalty_amount',
        'refund_amount',
        'admin_notes',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'refund_amount'  => 'decimal:2',
        'resolved_at'    => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function activities()
    {
        return $this->hasMany(InvestigationActivity::class, 'investigation_id');
    }
}
