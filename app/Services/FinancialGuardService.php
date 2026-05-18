<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\EscrowTransaction;
use App\Models\User;
use App\Models\InvestigationLog;

class FinancialGuardService
{
    public function assertPositiveAmount(float $amount, string $label = 'Amount'): void
    {
        if ($amount <= 0) {
            throw new \RuntimeException("{$label} must be positive, got {$amount}");
        }
    }

    public function assertSufficientBalance($wallet, float $required, string $label = 'Wallet'): void
    {
        $balance = (float) ($wallet->amount ?? 0);
        if ($balance < $required) {
            throw new \RuntimeException(
                "Insufficient {$label} balance. Required: {$required}, Available: {$balance}"
            );
        }
    }

    public function assertBookingState(Booking $booking, array $allowedStatuses, string $action): void
    {
        if (! in_array($booking->status, $allowedStatuses)) {
            throw new \RuntimeException(
                "Cannot {$action}: booking #{$booking->id} status [{$booking->status}] not in allowed: " . implode(', ', $allowedStatuses)
            );
        }
    }

    public function assertUserActive(User $user): void
    {
        if ((int) $user->status !== 1) {
            throw new \RuntimeException("User #{$user->id} is not active (status={$user->status})");
        }
    }

    public function assertNoActiveDispute(Booking $booking): void
    {
        $hasDispute = InvestigationLog::where('booking_id', $booking->id)
            ->whereIn('status', ['open', 'under_investigation', 'under_review'])
            ->exists();
        if ($hasDispute) {
            throw new \RuntimeException("Booking #{$booking->id} has an active dispute — operation blocked.");
        }
    }

    public function assertNotAlreadyReleased(EscrowTransaction $escrow): void
    {
        if ($escrow->status === 'released') {
            throw new \RuntimeException("Escrow #{$escrow->id} is already released.");
        }
        if ($escrow->released_at) {
            throw new \RuntimeException("Double-release detected for escrow #{$escrow->id}.");
        }
    }

    public function assertNotAlreadyRefunded(EscrowTransaction $escrow): void
    {
        if ($escrow->status === 'refunded') {
            throw new \RuntimeException("Escrow #{$escrow->id} is already refunded.");
        }
    }

    public function assertBookingNotRefunded(Booking $booking): void
    {
        if ($booking->payment_status === 'refunded') {
            throw new \RuntimeException("Booking #{$booking->id} already refunded.");
        }
    }

    public function assertValidEscrowState(EscrowTransaction $escrow, array $allowedStatuses): void
    {
        if (! in_array($escrow->status, $allowedStatuses)) {
            throw new \RuntimeException(
                "Escrow #{$escrow->id} status [{$escrow->status}] not in allowed: " . implode(', ', $allowedStatuses)
            );
        }
    }

    public function assertNoDuplicateAction(string $lockKey, int $actingAdminId): void
    {
        $existing = \App\Models\AdminActionLock::where('lock_key', $lockKey)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing) {
            $adminName = optional($existing->admin)->display_name ?? "Admin #{$existing->admin_id}";
            throw new \RuntimeException("Action already executed by {$adminName} at {$existing->created_at}.");
        }
    }

    public function checkWalletConsistency(User $user): array
    {
        $wallet = $user->wallet;
        if (! $wallet) return ['consistent' => true, 'message' => 'No wallet'];

        $walletBalance = (float) $wallet->amount;

        $escrowHeld = EscrowTransaction::where(function ($q) use ($user) {
            $q->where('customer_id', $user->id)->orWhere('provider_id', $user->id);
        })->whereIn('status', ['held', 'frozen_under_investigation'])
        ->sum('held_amount');

        $ledgerTotal = \App\Models\FinancialLedger::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        $difference = abs($walletBalance - abs($ledgerTotal));
        $consistent = $difference < 0.01;

        return [
            'consistent'     => $consistent,
            'wallet_balance' => $walletBalance,
            'ledger_total'   => (float) $ledgerTotal,
            'escrow_held'    => (float) $escrowHeld,
            'difference'     => round($difference, 2),
        ];
    }
}
