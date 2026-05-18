<?php

namespace App\Console\Commands;

use App\Services\FailedTransactionService;
use Illuminate\Console\Command;

class RetryFailedTransactions extends Command
{
    protected $signature = 'sand:retry-failed';
    protected $description = 'Retry failed financial transactions with exponential backoff';

    public function handle(FailedTransactionService $service): int
    {
        $this->info('Processing failed transactions...');

        $results = $service->processPending();

        $this->info("Processed: {$results['processed']}");
        $this->info("Recovered: {$results['recovered']}");
        $this->info("Failed: {$results['failed']}");

        if ($results['failed'] > 0) {
            $this->warn("{$results['failed']} transactions still failing.");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
