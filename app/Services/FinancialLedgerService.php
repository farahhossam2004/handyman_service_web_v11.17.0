<?php

namespace App\Services;

use App\Models\FinancialLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinancialLedgerService
{
    public function record(
        User $user,
        string $type,
        float $amount,
        float $balanceBefore,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $transactionKey = null,
        ?int $createdBy = null,
    ): FinancialLedger {
        $balanceAfter = $balanceBefore + $amount;

        return FinancialLedger::create([
            'user_id'         => $user->id,
            'type'            => $type,
            'amount'          => $amount,
            'balance_before'  => $balanceBefore,
            'balance_after'   => $balanceAfter,
            'currency'        => 'SAR',
            'reference_type'  => $referenceType,
            'reference_id'    => $referenceId,
            'status'          => 'confirmed',
            'description'     => $description,
            'transaction_key' => $transactionKey,
            'created_by'      => $createdBy ?? $user->id,
        ]);
    }

    public function recordCommission(
        User $user,
        float $amount,
        float $balanceBefore,
        int $bookingId,
        ?string $transactionKey = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'commission', -$amount, $balanceBefore,
            "Commission deduction of {$amount} SAR for booking #{$bookingId}.",
            'booking', $bookingId, $transactionKey,
        );
    }

    public function recordEscrowHold(
        User $user,
        float $amount,
        int $bookingId,
        ?string $transactionKey = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'escrow_hold', -$amount, 0,
            "Payment of {$amount} SAR held in escrow for booking #{$bookingId}.",
            'booking', $bookingId, $transactionKey,
        );
    }

    public function recordEscrowRelease(
        User $user,
        float $amount,
        float $balanceBefore,
        int $bookingId,
        ?string $transactionKey = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'escrow_release', $amount, $balanceBefore,
            "Escrow of {$amount} SAR released for booking #{$bookingId}.",
            'booking', $bookingId, $transactionKey,
        );
    }

    public function recordRefund(
        User $user,
        float $amount,
        float $balanceBefore,
        int $bookingId,
        ?string $transactionKey = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'refund', $amount, $balanceBefore,
            "Refund of {$amount} SAR for booking #{$bookingId}.",
            'booking', $bookingId, $transactionKey,
        );
    }

    public function recordInsuranceDeduction(
        User $user,
        float $amount,
        float $balanceBefore,
        ?string $reason = null,
        ?string $transactionKey = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'insurance_deduction', -$amount, $balanceBefore,
            $reason ?? "Insurance deduction of {$amount} SAR.",
            'user', $user->id, $transactionKey,
        );
    }

    public function recordAdjustment(
        User $user,
        float $amount,
        float $balanceBefore,
        string $description,
        ?string $transactionKey = null,
        ?int $createdBy = null,
    ): FinancialLedger {
        return $this->record(
            $user, 'adjustment', $amount, $balanceBefore,
            $description, null, null, $transactionKey, $createdBy,
        );
    }

    public function getUserLedger(int $userId, array $filters = []): array
    {
        $query = FinancialLedger::where('user_id', $userId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
