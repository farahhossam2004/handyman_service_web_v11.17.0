<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedTransactionQueue extends Model
{
    protected $table = 'failed_transaction_queue';

    protected $fillable = [
        'financial_trace_id', 'operation_type',
        'operable_type', 'operable_id',
        'amount', 'context', 'error_message', 'error_trace',
        'retry_count', 'last_retry_at', 'next_retry_at',
        'status', 'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'retry_count'   => 'integer',
        'last_retry_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'context'       => 'array',
    ];

    public function operable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
