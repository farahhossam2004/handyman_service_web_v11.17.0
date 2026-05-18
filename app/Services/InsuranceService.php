<?php

namespace App\Services;

use App\Models\User;
use App\Models\InsuranceTransaction;
use App\Models\Booking;
use App\Models\InvestigationLog;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class InsuranceService
{
    public function __construct(
        protected FinancialLedgerService $ledger,
    ) {}

    public function getConfig(): array
    {
        $setting = DB::table('settings')
            ->where('type', 'insurance')
            ->where('key', 'insurance-config')
            ->first();

        return $setting
            ? json_decode($setting->value, true)
            : ['default_target' => 100, 'currency' => 'SAR', 'allow_gradual' => true, 'gradual_percentage' => 10, 'auto_deduct' => true];
    }

    public function deposit(User $user, float $amount, int $actionedBy): InsuranceTransaction
    {
        $this->guardProviderActive($user);

        return DB::transaction(function () use ($user, $amount, $actionedBy) {
            $balanceBefore = $user->insurance_balance;

            $user->increment('insurance_balance', $amount);
            $user->insurance_status = $this->determineStatus($user->insurance_balance, $user->insurance_target);
            $user->save();

            $wallet = $user->wallet;
            if ($wallet) {
                $walletBalanceBefore = (float) $wallet->amount;
                $wallet->decrement('amount', $amount);
                $this->ledger->recordAdjustment($user, -$amount, $walletBalanceBefore,
                    "Insurance deposit of {$amount} SAR", null, $actionedBy);
            }

            return InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $amount,
                'type'           => 'deposit',
                'direction'      => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => 'Direct insurance deposit',
                'actioned_by'    => $actionedBy,
            ]);
        });
    }

    public function deductGradually(User $user, float $payoutAmount): float
    {
        if ($user->insurance_status === 'active' || $user->insurance_status === 'completed') return 0;
        $this->guardProviderActive($user);
        if ($this->hasActiveDispute($user)) return 0;

        $config = $this->getConfig();
        $deductionPercent = $config['gradual_percentage'] ?? 10;
        $deductionAmount = round($payoutAmount * ($deductionPercent / 100), 2);

        $shortfall = $user->insurance_target - $user->insurance_balance;
        $actualDeduction = min($deductionAmount, $shortfall);

        if ($actualDeduction <= 0) return 0;

        DB::transaction(function () use ($user, $actualDeduction, $deductionPercent) {
            $balanceBefore = $user->insurance_balance;

            $user->increment('insurance_balance', $actualDeduction);
            $user->insurance_status = $this->determineStatus($user->insurance_balance, $user->insurance_target);
            $user->save();

            $wallet = $user->wallet;
            if ($wallet) {
                $walletBalanceBefore = (float) $wallet->amount;
                $wallet->decrement('amount', $actualDeduction);
                $this->ledger->recordInsuranceDeduction($user, $actualDeduction, $walletBalanceBefore,
                    "Gradual insurance deduction ({$deductionPercent}%) from payout");
            }

            InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $actualDeduction,
                'type'           => 'gradual_deduction',
                'direction'      => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => "Gradual deduction from payout ({$deductionPercent}%)",
                'actioned_by'    => 1,
            ]);
        });

        return $actualDeduction;
    }

    public function deductPenalty(User $user, float $amount, string $reason, int $actionedBy): InsuranceTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason, $actionedBy) {
            $this->guardPositiveAmount($amount);

            $balanceBefore = $user->insurance_balance;
            $actualDeduction = min($amount, $user->insurance_balance);

            $user->decrement('insurance_balance', $actualDeduction);
            $user->insurance_status = $user->insurance_balance <= 0
                ? 'unpaid'
                : $this->determineStatus($user->insurance_balance, $user->insurance_target);
            $user->save();

            $this->ledger->recordInsuranceDeduction($user, $actualDeduction, $balanceBefore,
                "Penalty: {$reason}");

            return InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $actualDeduction,
                'type'           => 'penalty',
                'direction'      => 'debit',
                'balance_before' => $balanceBefore,
                'balance_after'  => $user->insurance_balance,
                'reason'         => $reason,
                'actioned_by'    => $actionedBy,
            ]);
        });
    }

    public function freeze(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->frozen_amount = $user->insurance_balance;
            $user->insurance_status = 'frozen';
            $user->save();
        });
    }

    public function unfreeze(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->frozen_amount = 0;
            $user->insurance_status = $this->determineStatus($user->insurance_balance, $user->insurance_target);
            $user->save();
        });
    }

    public function refundOnClosure(User $user, int $actionedBy): float
    {
        return DB::transaction(function () use ($user, $actionedBy) {
            $refundAmount = $user->insurance_balance;
            if ($refundAmount <= 0) return 0;

            $balanceBefore = $user->insurance_balance;

            $user->insurance_balance = 0;
            $user->insurance_status = 'refunded';
            $user->save();

            InsuranceTransaction::create([
                'user_id'        => $user->id,
                'related_type'   => User::class,
                'related_id'     => $user->id,
                'amount'         => $refundAmount,
                'type'           => 'refund',
                'direction'      => 'debit',
                'balance_before' => $balanceBefore,
                'balance_after'  => 0,
                'reason'         => 'Full insurance refund on account closure',
                'actioned_by'    => $actionedBy,
            ]);

            $wallet = $user->wallet ?? Wallet::create(['user_id' => $user->id, 'amount' => 0]);
            $walletBalanceBefore = (float) $wallet->amount;
            $wallet->increment('amount', $refundAmount);

            $this->ledger->recordAdjustment($user, $refundAmount, $walletBalanceBefore,
                "Insurance refund on account closure", null, $actionedBy);

            return $refundAmount;
        });
    }

    public function isCovered(User $user): bool
    {
        return in_array($user->insurance_status, ['active', 'completed'])
            && $user->insurance_balance >= $user->insurance_target;
    }

    public function getWithdrawableBalance(User $user): float
    {
        if (! $user->wallet) return 0;
        $protected = max($user->insurance_balance, $user->frozen_amount);
        return max(0, $user->wallet->amount - $protected);
    }

    // ── Private ─────────────────────────────────────────────────────────────────

    private function guardProviderActive(User $user): void
    {
        if ($user->status === 0) {
            throw new \RuntimeException("Cannot process insurance for suspended provider #{$user->id}.");
        }
    }

    private function guardPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount must be positive, got {$amount}");
        }
    }

    private function determineStatus(float $balance, float $target): string
    {
        if ($balance <= 0) return 'unpaid';
        if ($balance >= $target) return 'active';
        return 'partial';
    }

    private function hasActiveDispute(User $user): bool
    {
        if ($user->user_type !== 'provider') return false;
        return InvestigationLog::where('provider_id', $user->id)
            ->whereIn('status', ['open', 'under_investigation', 'under_review'])
            ->exists();
    }
}
