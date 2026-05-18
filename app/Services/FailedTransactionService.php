<?php

namespace App\Services;

use App\Models\FailedTransactionQueue;
use Illuminate\Support\Facades\DB;

class FailedTransactionService
{
    const MAX_RETRIES = 3;

    public function __construct(
        protected FinancialTransactionManager $ftm,
    ) {}

    public function retry(FailedTransactionQueue $failed): array
    {
        if ($failed->status === 'recovered') {
            return ['success' => true, 'message' => 'Already recovered.'];
        }

        if ($failed->retry_count >= self::MAX_RETRIES) {
            $failed->update(['status' => 'permanently_failed']);
            return ['success' => false, 'message' => 'Max retries reached.'];
        }

        $failed->update(['status' => 'processing', 'last_retry_at' => now()]);

        try {
            $context = json_decode($failed->context, true) ?? [];

            $result = match ($failed->operation_type) {
                'escrow_hold' => $this->retryEscrowHold($failed, $context),
                'escrow_release' => $this->retryEscrowRelease($failed, $context),
                'refund' => $this->retryRefund($failed, $context),
                'commission' => $this->retryCommission($failed, $context),
                'insurance' => $this->retryInsurance($failed, $context),
                'adjustment' => $this->retryAdjustment($failed, $context),
                default => throw new \RuntimeException("Unknown operation: {$failed->operation_type}"),
            };

            $failed->update(['status' => 'recovered', 'retry_count' => $failed->retry_count + 1]);

            return ['success' => true, 'message' => 'Recovered.'];
        } catch (\Throwable $e) {
            $newCount = $failed->retry_count + 1;
            $nextRetry = now()->addMinutes(min(5 * pow(2, $newCount), 120));

            $failed->update([
                'status'        => $newCount >= self::MAX_RETRIES ? 'permanently_failed' : 'pending_retry',
                'retry_count'   => $newCount,
                'error_message' => $e->getMessage(),
                'error_trace'   => $e->getTraceAsString(),
                'last_retry_at' => now(),
                'next_retry_at' => $newCount >= self::MAX_RETRIES ? null : $nextRetry,
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function processPending(): array
    {
        $results = ['processed' => 0, 'recovered' => 0, 'failed' => 0];

        FailedTransactionQueue::where('status', 'pending_retry')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                  ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('created_at')
            ->chunk(50, function ($items) use (&$results) {
                foreach ($items as $item) {
                    $results['processed']++;
                    $result = $this->retry($item);
                    if ($result['success']) {
                        $results['recovered']++;
                    } else {
                        $results['failed']++;
                    }
                }
            });

        return $results;
    }

    protected function retryEscrowHold(FailedTransactionQueue $failed, array $context): void
    {
        $booking = \App\Models\Booking::find($failed->operable_id);
        if (! $booking) throw new \RuntimeException("Booking #{$failed->operable_id} not found");
    }

    protected function retryEscrowRelease(FailedTransactionQueue $failed, array $context): void
    {
        $booking = \App\Models\Booking::find($failed->operable_id);
        if (! $booking) throw new \RuntimeException("Booking #{$failed->operable_id} not found");
        $this->ftm->releaseEscrow($booking, $failed->created_by ?? 1);
    }

    protected function retryRefund(FailedTransactionQueue $failed, array $context): void
    {
        $booking = \App\Models\Booking::find($failed->operable_id);
        if (! $booking) throw new \RuntimeException("Booking #{$failed->operable_id} not found");
    }

    protected function retryCommission(FailedTransactionQueue $failed, array $context): void
    {
        $booking = \App\Models\Booking::find($failed->operable_id);
        if (! $booking) throw new \RuntimeException("Booking #{$failed->operable_id} not found");
        $this->ftm->deductCommission($booking, $failed->created_by ?? 1);
    }

    protected function retryInsurance(FailedTransactionQueue $failed, array $context): void
    {
        throw new \RuntimeException('Insurance retry not supported');
    }

    protected function retryAdjustment(FailedTransactionQueue $failed, array $context): void
    {
        $user = \App\Models\User::find($failed->operable_id);
        if (! $user) throw new \RuntimeException("User #{$failed->operable_id} not found");
    }

    public function getPending(int $limit = 50): array
    {
        return FailedTransactionQueue::where('status', 'pending_retry')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getFailed(int $limit = 50): array
    {
        return FailedTransactionQueue::where('status', 'permanently_failed')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }
}
