<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTraceLog extends Model
{
    protected $table = 'financial_trace_logs';
    public $timestamps = false;

    protected $fillable = [
        'financial_trace_id', 'operation_type', 'step',
        'service', 'amount', 'context', 'message', 'created_at',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'context'    => 'array',
        'created_at' => 'datetime',
    ];
}
