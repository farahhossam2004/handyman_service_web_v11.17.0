<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialLedger extends Model
{
    protected $table = 'financial_ledger';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'amount', 'balance_before', 'balance_after',
        'currency', 'reference_id', 'reference_type', 'status',
        'description', 'transaction_key', 'created_by', 'created_at',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'created_at'     => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
