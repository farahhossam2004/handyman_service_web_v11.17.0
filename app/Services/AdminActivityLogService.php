<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\Request;

class AdminActivityLogService
{
    public function log(
        int $adminId,
        string $actionType,
        ?int $targetUserId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?int $referenceId = null,
        ?string $referenceType = null,
    ): AdminActivityLog {
        return AdminActivityLog::create([
            'admin_id'        => $adminId,
            'action_type'     => $actionType,
            'target_user_id'  => $targetUserId,
            'reference_id'    => $referenceId,
            'reference_type'  => $referenceType,
            'metadata'        => $metadata ? json_encode($metadata) : null,
            'description'     => $description,
            'ip_address'      => Request::ip(),
        ]);
    }

    public function logFreeze(int $adminId, int $targetUserId, int $bookingId, string $reason): AdminActivityLog
    {
        return $this->log($adminId, 'freeze', $targetUserId,
            "Frozen funds for booking #{$bookingId}: {$reason}",
            ['booking_id' => $bookingId, 'reason' => $reason],
            $bookingId, 'booking'
        );
    }

    public function logRelease(int $adminId, int $targetUserId, int $bookingId, float $amount): AdminActivityLog
    {
        return $this->log($adminId, 'release', $targetUserId,
            "Released {$amount} SAR escrow for booking #{$bookingId}",
            ['booking_id' => $bookingId, 'amount' => $amount],
            $bookingId, 'booking'
        );
    }

    public function logAdjustWallet(int $adminId, int $targetUserId, float $amount, string $reason): AdminActivityLog
    {
        return $this->log($adminId, 'adjust_wallet', $targetUserId,
            "Wallet adjustment of {$amount} SAR: {$reason}",
            ['amount' => $amount, 'reason' => $reason],
        );
    }

    public function logResolveDispute(int $adminId, int $targetUserId, int $investigationId, string $resolution): AdminActivityLog
    {
        return $this->log($adminId, 'resolve_dispute', $targetUserId,
            "Dispute #{$investigationId} resolved as: {$resolution}",
            ['investigation_id' => $investigationId, 'resolution' => $resolution],
            $investigationId, 'investigation'
        );
    }

    public function logRefund(int $adminId, int $targetUserId, int $bookingId, float $amount): AdminActivityLog
    {
        return $this->log($adminId, 'refund', $targetUserId,
            "Refund of {$amount} SAR processed for booking #{$bookingId}",
            ['booking_id' => $bookingId, 'amount' => $amount],
            $bookingId, 'booking'
        );
    }

    public function getRecentByAdmin(int $adminId, int $limit = 50): array
    {
        return AdminActivityLog::where('admin_id', $adminId)
            ->with('targetUser:id,display_name,email')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }

    public function getRecent(int $limit = 100): array
    {
        return AdminActivityLog::with(['admin:id,display_name', 'targetUser:id,display_name,email'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->toArray();
    }
}
