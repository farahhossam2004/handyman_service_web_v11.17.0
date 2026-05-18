<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\EscrowTransaction;
use App\Models\Wallet;
use App\Models\WalletHistory;
use App\Models\FinancialLedger;
use App\Models\InvestigationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EscrowService
{
    public function __construct(
        protected FinancialLedgerService $ledger,
        protected AdminActivityLogService $adminLog,
    ) {}

    public function hold(Booking $booking, Payment $payment, int $actionedBy): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $payment, $actionedBy) {
            $key = $this->makeKey('hold', $booking->id);

            $escrow = EscrowTransaction::create([
                'escrowable_type' => Booking::class,
                'escrowable_id'   => $booking->id,
                'customer_id'     => $booking->customer_id,
                'provider_id'     => $booking->provider_id,
                'payment_id'      => $payment->id,
                'amount'          => $payment->total_amount,
                'held_amount'     => $payment->total_amount,
                'status'          => 'held',
                'held_at'         => now(),
                'actioned_by'     => $actionedBy,
                'idempotency_key' => $key,
                'notes'           => 'Payment held in escrow after quote approval.',
            ]);

            $booking->payment_status = 'escrow';
            $booking->save();

            $this->ledger->recordEscrowHold($booking->customer, $payment->total_amount, $booking->id, $key);

            WalletHistory::create([
                'user_id'          => $booking->customer_id,
                'datetime'         => now(),
                'activity_type'    => 'payment_held',
                'activity_message' => "Payment of {$payment->total_amount} SAR held in escrow for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $payment->total_amount,
                    'transaction_type'    => 'debit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    public function release(Booking $booking, int $actionedBy): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $actionedBy) {
            $escrow = $this->findActiveEscrow($booking);

            if ($escrow->status === 'released') {
                throw new \RuntimeException("Escrow for booking #{$booking->id} is already released.");
            }
            if ($escrow->released_at) {
                throw new \RuntimeException("Double-release detected for booking #{$booking->id}.");
            }
            $this->guardBookingCompleted($booking);

            $key = $this->makeKey('release', $booking->id);

            $escrow->update([
                'status'           => 'released',
                'released_amount'  => $escrow->held_amount,
                'held_amount'      => 0,
                'released_at'      => now(),
                'idempotency_key'  => $key,
                'actioned_by'      => $actionedBy,
                'notes'            => 'Released after job completion.',
            ]);

            $booking->payment_status = 'released';
            $booking->save();

            $providerWallet = $booking->provider->wallet ?? Wallet::create(['user_id' => $booking->provider_id, 'amount' => 0]);
            $balanceBefore = (float) $providerWallet->amount;
            $providerWallet->increment('amount', $escrow->released_amount);
            $providerWallet->syncBalances();

            $this->ledger->recordEscrowRelease($booking->provider, $escrow->released_amount, $balanceBefore, $booking->id, $key);

            WalletHistory::create([
                'user_id'          => $booking->provider_id,
                'datetime'         => now(),
                'activity_type'    => 'escrow_released',
                'activity_message' => "Escrow of {$escrow->amount} SAR released for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $escrow->released_amount,
                    'transaction_type'    => 'credit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    public function refund(Booking $booking, int $actionedBy, ?string $reason = null): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $actionedBy, $reason) {
            $escrow = $this->findActiveEscrow($booking);

            if ($escrow->status === 'refunded') {
                throw new \RuntimeException("Escrow for booking #{$booking->id} is already refunded.");
            }

            $key = $this->makeKey('refund', $booking->id);

            $escrow->update([
                'status'           => 'refunded',
                'refunded_amount'  => $escrow->held_amount,
                'held_amount'      => 0,
                'refunded_at'      => now(),
                'idempotency_key'  => $key,
                'actioned_by'      => $actionedBy,
                'notes'            => $reason ?? 'Full refund processed.',
            ]);

            $booking->payment_status = 'refunded';
            $booking->save();

            $customerWallet = $booking->customer->wallet ?? Wallet::create(['user_id' => $booking->customer_id, 'amount' => 0]);
            $balanceBefore = (float) $customerWallet->amount;
            $customerWallet->increment('amount', $escrow->refunded_amount);
            $customerWallet->syncBalances();

            $this->ledger->recordRefund($booking->customer, $escrow->refunded_amount, $balanceBefore, $booking->id, $key);

            WalletHistory::create([
                'user_id'          => $booking->customer_id,
                'datetime'         => now(),
                'activity_type'    => 'escrow_refunded',
                'activity_message' => "Escrow of {$escrow->amount} SAR refunded for booking #{$booking->id}.",
                'activity_data'    => json_encode([
                    'credit_debit_amount' => $escrow->refunded_amount,
                    'transaction_type'    => 'credit',
                    'booking_id'          => $booking->id,
                    'escrow_id'           => $escrow->id,
                ]),
            ]);

            return $escrow;
        });
    }

    public function freeze(Booking $booking, int $actionedBy): EscrowTransaction
    {
        $escrow = $this->findActiveEscrow($booking);

        $escrow->update([
            'status'      => 'frozen_under_investigation',
            'frozen_at'   => now(),
            'actioned_by' => $actionedBy,
            'notes'       => 'Frozen due to dispute investigation.',
        ]);

        $providerWallet = $booking->provider->wallet;
        if ($providerWallet) {
            $providerWallet->syncBalances();
        }

        return $escrow;
    }

    public function deductPenalty(Booking $booking, float $penaltyAmount, int $actionedBy, string $reason): EscrowTransaction
    {
        return DB::transaction(function () use ($booking, $penaltyAmount, $actionedBy, $reason) {
            $this->guardPositiveAmount($penaltyAmount);

            $escrow = EscrowTransaction::where('escrowable_id', $booking->id)
                ->where('escrowable_type', Booking::class)
                ->where('status', 'frozen_under_investigation')
                ->latest()
                ->firstOrFail();

            $newHeld = $escrow->held_amount - $penaltyAmount;

            $escrow->update([
                'held_amount'      => max(0, $newHeld),
                'penalty_deducted' => $escrow->penalty_deducted + $penaltyAmount,
                'notes'            => "Penalty deducted: {$penaltyAmount} SAR. Reason: {$reason}",
                'actioned_by'      => $actionedBy,
            ]);

            $providerBalanceBefore = (float) ($booking->provider->wallet->amount ?? 0);
            $this->ledger->recordAdjustment(
                $booking->provider,
                -$penaltyAmount,
                $providerBalanceBefore,
                "Penalty deducted from escrow: {$reason}",
                $this->makeKey('penalty', $booking->id),
                $actionedBy,
            );

            return $escrow->fresh();
        });
    }

    public function getUserHistory(int $userId): array
    {
        return EscrowTransaction::where(function ($q) use ($userId) {
            $q->where('customer_id', $userId)
              ->orWhere('provider_id', $userId);
        })
        ->with(['escrowable', 'payment'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->toArray();
    }

    public function getDashboardStats(): array
    {
        return [
            'total_held'      => EscrowTransaction::where('status', 'held')->sum('held_amount'),
            'total_released'  => EscrowTransaction::where('status', 'released')->sum('released_amount'),
            'total_refunded'  => EscrowTransaction::where('status', 'refunded')->sum('refunded_amount'),
            'total_frozen'    => EscrowTransaction::where('status', 'frozen_under_investigation')->sum('held_amount'),
            'total_penalties' => EscrowTransaction::sum('penalty_deducted'),
            'count_active'    => EscrowTransaction::whereIn('status', ['held', 'frozen_under_investigation'])->count(),
        ];
    }

    // ── Private Guards ────────────────────────────────────────────────────────

    private function findActiveEscrow(Booking $booking): EscrowTransaction
    {
        return EscrowTransaction::where('escrowable_id', $booking->id)
            ->where('escrowable_type', Booking::class)
            ->whereIn('status', ['held', 'frozen_under_investigation'])
            ->latest()
            ->firstOrFail();
    }

    private function guardBookingCompleted(Booking $booking): void
    {
        if (! in_array($booking->status, ['completed', 'resolved'])) {
            throw new \RuntimeException(
                "Cannot release escrow: booking #{$booking->id} is not completed."
            );
        }
        $hasOpenDispute = InvestigationLog::where('booking_id', $booking->id)
            ->whereIn('status', ['open', 'under_investigation', 'under_review'])
            ->exists();
        if ($hasOpenDispute) {
            throw new \RuntimeException(
                "Cannot release escrow: booking #{$booking->id} has an open dispute."
            );
        }
    }

    private function guardPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount must be positive, got {$amount}");
        }
    }

    private function makeKey(string $prefix, int $id): string
    {
        return "{$prefix}_{$id}_" . Str::random(8);
    }
}
