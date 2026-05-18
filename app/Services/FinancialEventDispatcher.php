<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\FinancialLedger;
use Illuminate\Support\Facades\Cache;

class FinancialEventDispatcher
{
    public function __construct(
        protected AdminActivityLogService $adminLog,
        protected FinancialLedgerService $ledger,
    ) {}

    public function dispatch(string $eventType, array $payload): void
    {
        $method = 'on' . str_replace('_', '', ucwords($eventType, '_'));
        if (method_exists($this, $method)) {
            $this->$method($payload);
        }
    }

    protected function onEscrowHeld(array $p): void
    {
        $booking = $p['booking'];
        $this->logAudit($p['trace_id'], 'escrow_hold', $booking->provider_id, $p['amount'], "Escrow held for booking #{$booking->id}");
        $this->invalidateCache();
    }

    protected function onEscrowReleased(array $p): void
    {
        $booking = $p['booking'];
        $this->logAudit($p['trace_id'], 'escrow_release', $booking->provider_id, $p['amount'], "Escrow released for booking #{$booking->id}");
        $this->invalidateCache();
    }

    protected function onRefundProcessed(array $p): void
    {
        $booking = $p['booking'];
        $this->logAudit($p['trace_id'], 'refund', $booking->customer_id, $p['amount'], "Refund processed for booking #{$booking->id}");
        $this->invalidateCache();
    }

    protected function onCommissionApplied(array $p): void
    {
        $booking = $p['booking'];
        $this->logAudit($p['trace_id'], 'commission', $booking->provider_id, $p['amount'], "Commission deducted for booking #{$booking->id}");
        $this->invalidateCache();
    }

    protected function onInsuranceUpdated(array $p): void
    {
        Cache::forget('sand.admin.metrics');
    }

    protected function onDisputeOpened(array $p): void
    {
        $this->invalidateCache();
    }

    protected function logAudit(string $traceId, string $action, ?int $userId, float $amount, string $description): void
    {
        try {
            AdminActivityLog::create([
                'admin_id'       => 1,
                'action_type'    => $action,
                'target_user_id' => $userId,
                'metadata'       => json_encode([
                    'trace_id' => $traceId,
                    'amount'   => $amount,
                    'event'    => true,
                ]),
                'description'    => $description,
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Event audit log failed: {$e->getMessage()}");
        }
    }

    protected function invalidateCache(): void
    {
        DashboardService::clearCache();
    }
}
