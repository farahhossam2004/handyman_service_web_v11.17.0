<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationReport extends Model
{
    protected $table = 'reconciliation_reports';

    protected $fillable = [
        'report_date', 'status',
        'summary', 'wallet_checks', 'escrow_checks',
        'insurance_checks', 'ledger_checks',
        'discrepancies', 'total_checked', 'total_errors',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'report_date'      => 'date',
        'summary'          => 'array',
        'wallet_checks'    => 'array',
        'escrow_checks'    => 'array',
        'insurance_checks' => 'array',
        'ledger_checks'    => 'array',
        'discrepancies'    => 'array',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];
}
