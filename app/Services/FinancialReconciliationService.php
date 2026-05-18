<?php

namespace App\Services;

use App\Models\ReconciliationReport;
use App\Models\Wallet;
use App\Models\EscrowTransaction;
use App\Models\User;
use App\Models\FinancialLedger;
use Illuminate\Support\Facades\DB;

class FinancialReconciliationService
{
    public function run(): ReconciliationReport
    {
        $startedAt = now();
        $walletChecks = $this->checkWallets();
        $escrowChecks = $this->checkEscrow();
        $insuranceChecks = $this->checkInsurance();
        $ledgerChecks = $this->checkLedger();

        $allDiscrepancies = array_merge(
            $walletChecks['discrepancies'] ?? [],
            $escrowChecks['discrepancies'] ?? [],
            $insuranceChecks['discrepancies'] ?? [],
            $ledgerChecks['discrepancies'] ?? [],
        );

        $totalErrors = count($allDiscrepancies);
        $status = $totalErrors === 0 ? 'balanced' : ($totalErrors < 5 ? 'warning' : 'critical_mismatch');

        $report = ReconciliationReport::updateOrCreate(
            ['report_date' => now()->toDateString()],
            [
                'status'          => $status,
                'summary'         => json_encode([
                    'wallets_checked'    => $walletChecks['total'] ?? 0,
                    'wallets_errors'     => count($walletChecks['discrepancies'] ?? []),
                    'escrow_checked'     => $escrowChecks['total'] ?? 0,
                    'escrow_errors'      => count($escrowChecks['discrepancies'] ?? []),
                    'insurance_checked'  => $insuranceChecks['total'] ?? 0,
                    'insurance_errors'   => count($insuranceChecks['discrepancies'] ?? []),
                    'ledger_entries'     => $ledgerChecks['total'] ?? 0,
                    'ledger_errors'      => count($ledgerChecks['discrepancies'] ?? []),
                    'total_errors'       => $totalErrors,
                    'status'             => $status,
                ]),
                'wallet_checks'    => json_encode($walletChecks),
                'escrow_checks'    => json_encode($escrowChecks),
                'insurance_checks' => json_encode($insuranceChecks),
                'ledger_checks'    => json_encode($ledgerChecks),
                'discrepancies'    => json_encode($allDiscrepancies),
                'total_checked'    => ($walletChecks['total'] ?? 0) + ($escrowChecks['total'] ?? 0) + ($insuranceChecks['total'] ?? 0) + ($ledgerChecks['total'] ?? 0),
                'total_errors'     => $totalErrors,
                'started_at'       => $startedAt,
                'completed_at'     => now(),
            ]
        );

        return $report;
    }

    protected function checkWallets(): array
    {
        $discrepancies = [];
        $total = 0;

        Wallet::chunk(100, function ($wallets) use (&$discrepancies, &$total) {
            foreach ($wallets as $wallet) {
                $total++;
                $user = User::find($wallet->user_id);
                if (! $user) continue;

                $ledgerTotal = FinancialLedger::where('user_id', $user->id)
                    ->where('status', 'confirmed')
                    ->sum('amount');

                $walletBalance = (float) $wallet->amount;
                $absLedger = abs($ledgerTotal);

                if (abs($walletBalance - $absLedger) > 0.02) {
                    $discrepancies[] = [
                        'type'           => 'wallet_mismatch',
                        'user_id'        => $user->id,
                        'wallet_balance' => $walletBalance,
                        'ledger_total'   => round($absLedger, 2),
                        'difference'     => round($walletBalance - $absLedger, 2),
                    ];
                }
            }
        });

        return ['total' => $total, 'discrepancies' => $discrepancies];
    }

    protected function checkEscrow(): array
    {
        $discrepancies = [];
        $total = EscrowTransaction::count();

        $orphaned = EscrowTransaction::doesntHave('escrowable')->get();
        foreach ($orphaned as $e) {
            $discrepancies[] = [
                'type'      => 'orphaned_escrow',
                'escrow_id' => $e->id,
                'booking'   => $e->escrowable_id,
                'amount'    => (float) $e->amount,
                'status'    => $e->status,
            ];
        }

        $staleHeld = EscrowTransaction::where('status', 'held')
            ->where('held_at', '<', now()->subDays(90))
            ->get();
        foreach ($staleHeld as $e) {
            $discrepancies[] = [
                'type'      => 'stale_escrow',
                'escrow_id' => $e->id,
                'held_at'   => $e->held_at?->toDateString(),
                'amount'    => (float) $e->amount,
                'days'      => now()->diffInDays($e->held_at),
            ];
        }

        return ['total' => $total, 'discrepancies' => $discrepancies];
    }

    protected function checkInsurance(): array
    {
        $discrepancies = [];
        $total = User::whereNotNull('insurance_balance')->count();

        User::where('insurance_balance', '<', 0)->chunk(100, function ($users) use (&$discrepancies) {
            foreach ($users as $user) {
                $discrepancies[] = [
                    'type'              => 'negative_insurance',
                    'user_id'           => $user->id,
                    'insurance_balance' => (float) $user->insurance_balance,
                ];
            }
        });

        User::where('insurance_status', 'active')
            ->where('insurance_balance', '<', 50)
            ->chunk(100, function ($users) use (&$discrepancies) {
                foreach ($users as $user) {
                    $discrepancies[] = [
                        'type'              => 'low_insurance_balance',
                        'user_id'           => $user->id,
                        'insurance_balance' => (float) $user->insurance_balance,
                        'target'            => (float) $user->insurance_target,
                    ];
                }
            });

        return ['total' => $total, 'discrepancies' => $discrepancies];
    }

    protected function checkLedger(): array
    {
        $discrepancies = [];
        $total = FinancialLedger::count();

        $duplicates = FinancialLedger::whereNotNull('transaction_key')
            ->select('transaction_key', DB::raw('COUNT(*) as count'))
            ->groupBy('transaction_key')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $discrepancies[] = [
                'type'             => 'duplicate_ledger_key',
                'transaction_key'  => $dup->transaction_key,
                'count'            => $dup->count,
            ];
        }

        return ['total' => $total, 'discrepancies' => $discrepancies];
    }

    public function getLatestReport(): ?ReconciliationReport
    {
        return ReconciliationReport::latest('report_date')->first();
    }

    public function getReportHistory(int $days = 30): array
    {
        return ReconciliationReport::where('report_date', '>=', now()->subDays($days))
            ->orderBy('report_date', 'desc')
            ->get()
            ->toArray();
    }
}
