<?php

namespace App\Console\Commands;

use App\Services\FinancialReconciliationService;
use Illuminate\Console\Command;

class RunFinancialReconciliation extends Command
{
    protected $signature = 'sand:reconcile';
    protected $description = 'Run daily financial reconciliation checks';

    public function handle(FinancialReconciliationService $reconciliation): int
    {
        $this->info('Starting financial reconciliation...');

        $report = $reconciliation->run();

        $this->info("Status: {$report->status}");
        $this->info("Total checked: {$report->total_checked}");
        $this->info("Total errors: {$report->total_errors}");

        if ($report->status === 'critical_mismatch') {
            $this->error('CRITICAL: Financial discrepancies detected!');
            return Command::FAILURE;
        }

        if ($report->status === 'warning') {
            $this->warn('Warning: Minor discrepancies found.');
        }

        $this->info('Reconciliation complete.');
        return Command::SUCCESS;
    }
}
