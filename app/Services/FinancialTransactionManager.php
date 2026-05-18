<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\EscrowTransaction;
use App\Models\FinancialLedger;
use App\Models\AdminActionLock;
use App\Models\FailedTransactionQueue;
use App\Models\FinancialTraceLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialTransactionManager
{
    public function __construct(
        protected FinancialGuardService $guard,
        protected FinancialLedgerService $ledger,
        protected EscrowService $escrowService,
        protected CommissionService $commissionService,
        protected InsuranceService $insuranceService,
        protected AdminActivityLogService $adminLog,
        protected FinancialEventDispatcher $events,
    ) {}

    public function holdEscrow(Booking $booking, Payment $payment, int $actionedBy, ?string $reason = null): array
    {
        return $this->execute('escrow_hold', $booking, $actionedBy, function ($traceId) use ($booking, $payment, $actionedBy, $reason) {
            $this->guard->assertBookingState($booking, ['quote_approved'], 'hold escrow');
            $this->guard->assertUserActive($booking->provider);
            $this->guard->assertNoActiveDispute($booking);
            $this->guard->assertPositiveAmount($payment->total_amount, 'Payment amount');

            $escrow = $this->escrowService->hold($booking, $payment, $actionedBy);

            $this->events->dispatch('escrow_held', [
                'booking'   => $booking,
                'escrow'    => $escrow,
                'amount'    => $payment->total_amount,
                'trace_id'  => $traceId,
            ]);

            return ['escrow' => $escrow, 'trace_id' => $traceId];
        }, $reason);
    }

    public function releaseEscrow(Booking $booking, int $actionedBy, ?string $reason = null): array
    {
        return $this->execute('escrow_release', $booking, $actionedBy, function ($traceId) use ($booking, $actionedBy) {
            $this->guard->assertBookingState($booking, ['completed', 'resolved'], 'release escrow');
            $this->guard->assertUserActive($booking->provider);
            $this->guard->assertNoActiveDispute($booking);
            $this->guard->assertBookingNotRefunded($booking);

            $lockKey = "release:booking:{$booking->id}";
            $this->guard->assertNoDuplicateAction($lockKey, $actionedBy);

            $escrow = $this->escrowService->release($booking, $actionedBy);

            $this->createLock($lockKey, $actionedBy, 'escrow_release', $booking, $reason);

            $this->events->dispatch('escrow_released', [
                'booking'  => $booking,
                'escrow'   => $escrow,
                'amount'   => $escrow->released_amount,
                'trace_id' => $traceId,
            ]);

            return ['escrow' => $escrow, 'trace_id' => $traceId];
        }, $reason);
    }

    public function refund(Booking $booking, float $amount, int $actionedBy, string $reason): array
    {
        return $this->execute('refund', $booking, $actionedBy, function ($traceId) use ($booking, $amount, $actionedBy, $reason) {
            $this->guard->assertBookingState($booking, ['cancelled', 'disputed', 'resolved'], 'refund');
            $this->guard->assertBookingNotRefunded($booking);
            $this->guard->assertPositiveAmount($amount);

            $lockKey = "refund:booking:{$booking->id}";
            $this->guard->assertNoDuplicateAction($lockKey, $actionedBy);

            $escrow = $this->escrowService->refund($booking, $actionedBy, $reason);

            $this->createLock($lockKey, $actionedBy, 'refund', $booking, $reason);

            $this->adminLog->logRefund($actionedBy, $booking->customer_id, $booking->id, $amount);

            $this->events->dispatch('refund_processed', [
                'booking'  => $booking,
                'escrow'   => $escrow,
                'amount'   => $amount,
                'trace_id' => $traceId,
            ]);

            return ['escrow' => $escrow, 'trace_id' => $traceId];
        }, $reason);
    }

    public function deductCommission(Booking $booking, int $actionedBy): array
    {
        return $this->execute('commission', $booking, $actionedBy, function ($traceId) use ($booking) {
            $this->guard->assertUserActive($booking->provider);
            $this->guard->assertNoActiveDispute($booking);

            $earning = $this->commissionService->apply($booking);

            $this->events->dispatch('commission_applied', [
                'booking'   => $booking,
                'earning'   => $earning,
                'amount'    => $earning->commission_amount,
                'trace_id'  => $traceId,
            ]);

            return ['earning' => $earning, 'trace_id' => $traceId];
        });
    }

    public function adjustWallet(User $user, float $amount, int $actionedBy, string $reason): array
    {
        return $this->execute('adjustment', $user, $actionedBy, function ($traceId) use ($user, $amount, $actionedBy, $reason) {
            $this->guard->assertUserActive($user);
            if ($amount < 0) {
                $wallet = $user->wallet;
                $this->guard->assertSufficientBalance($wallet, abs($amount), 'Wallet');
            }

            $wallet = $user->wallet;
            $balanceBefore = (float) ($wallet->amount ?? 0);

            DB::transaction(function () use ($wallet, $user, $amount, $balanceBefore, $actionedBy, $reason, $traceId) {
                if (!$wallet) {
                    $wallet = \App\Models\Wallet::create(['user_id' => $user->id, 'amount' => 0]);
                }
                $wallet->increment('amount', $amount);
                $wallet->syncBalances();

                $this->ledger->recordAdjustment($user, $amount, $balanceBefore, $reason, $traceId, $actionedBy);
            });

            $this->adminLog->logAdjustWallet($actionedBy, $user->id, $amount, $reason);

            return ['wallet' => $wallet->fresh(), 'trace_id' => $traceId];
        }, $reason);
    }

    protected function execute(string $operationType, $subject, int $actionedBy, callable $callback, ?string $reason = null): array
    {
        $traceId = $this->generateTraceId($operationType);
        $subjectType = $subject instanceof Booking ? 'booking' : 'user';
        $subjectId = $subject->id;

        $this->logTrace($traceId, $operationType, 'before_execution', null, [
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'actioned_by'  => $actionedBy,
            'reason'       => $reason,
        ]);

        try {
            $result = DB::transaction(function () use ($traceId, $operationType, $subject, $subjectType, $subjectId, $actionedBy, $callback, $reason) {
                $result = $callback($traceId);

                $this->logTrace($traceId, $operationType, 'after_execution', null, [
                    'subject_type' => $subjectType,
                    'subject_id'   => $subjectId,
                    'result'       => $result,
                ]);

                return $result;
            });

            \App\Services\DashboardService::clearCache();

            return ['success' => true, 'trace_id' => $traceId, 'data' => $result];
        } catch (\Throwable $e) {
            $this->logTrace($traceId, $operationType, 'failure', null, [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'subject' => $subjectType . '#' . $subjectId,
            ]);

            $this->enqueueFailed($traceId, $operationType, $subject, $actionedBy, $e);

            return [
                'success' => false,
                'trace_id' => $traceId,
                'error'    => $e->getMessage(),
                'code'     => $e->getCode() ?: 500,
            ];
        }
    }

    protected function generateTraceId(string $prefix): string
    {
        return strtoupper($prefix) . '_' . now()->format('YmdHis') . '_' . Str::random(10);
    }

    protected function logTrace(string $traceId, string $operation, string $step, ?float $amount = null, ?array $context = null): void
    {
        try {
            FinancialTraceLog::create([
                'financial_trace_id' => $traceId,
                'operation_type'     => $operation,
                'step'               => $step,
                'amount'             => $amount,
                'context'            => $context ? json_encode($context) : null,
                'message'            => $context['error'] ?? "{$operation}: {$step}",
                'created_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Trace log failed: {$e->getMessage()}");
        }
    }

    protected function enqueueFailed(string $traceId, string $operationType, $subject, int $actionedBy, \Throwable $e): void
    {
        try {
            FailedTransactionQueue::create([
                'financial_trace_id' => $traceId,
                'operation_type'     => $operationType,
                'operable_type'      => get_class($subject),
                'operable_id'        => $subject->id,
                'context'            => json_encode(['reason' => $e->getMessage(), 'trace' => $e->getTraceAsString()]),
                'error_message'      => $e->getMessage(),
                'error_trace'        => $e->getTraceAsString(),
                'retry_count'        => 0,
                'status'             => 'pending_retry',
                'next_retry_at'      => now()->addMinutes(5),
                'created_by'         => $actionedBy,
            ]);
        } catch (\Throwable $logError) {
            \Illuminate\Support\Facades\Log::error("Failed to enqueue failed transaction: {$logError->getMessage()}");
        }
    }

    protected function createLock(string $lockKey, int $adminId, string $actionType, $lockable, ?string $reason = null): void
    {
        try {
            AdminActionLock::create([
                'lock_key'    => $lockKey,
                'admin_id'    => $adminId,
                'action_type' => $actionType,
                'lockable_type' => get_class($lockable),
                'lockable_id'   => $lockable->id,
                'reason'      => $reason,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
                'expires_at'  => now()->addDays(30),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Lock creation failed: {$e->getMessage()}");
        }
    }
}
