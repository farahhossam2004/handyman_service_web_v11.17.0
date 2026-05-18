<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\InvestigationLog;
use App\Models\EscrowTransaction;
use Illuminate\Support\Facades\DB;

class InvestigationService
{
    public function __construct(
        protected EscrowService $escrowService,
        protected InsuranceService $insuranceService,
    ) {}

    public function open(Booking $booking, int $adminId, string $reason): InvestigationLog
    {
        return DB::transaction(function () use ($booking, $adminId, $reason) {
            $investigation = InvestigationLog::create([
                'booking_id'     => $booking->id,
                'opened_by'      => $adminId,
                'dispute_reason' => $reason,
                'status'         => 'open',
                'resolution'     => 'pending',
            ]);

            $booking->status = 'disputed';
            $booking->dispute_reason = $reason;
            $booking->frozen_until = now()->addDays(14);
            $booking->save();

            $this->escrowService->freeze($booking, $adminId);

            if ($booking->provider) {
                $this->insuranceService->freeze($booking->provider);
            }

            $booking->activities()->create([
                'activity_type'    => 'investigation_opened',
                'activity_message' => "Investigation opened by admin: {$reason}",
                'created_by'       => $adminId,
            ]);

            return $investigation;
        });
    }

    public function escalate(int $investigationId, int $adminId): InvestigationLog
    {
        $investigation = InvestigationLog::findOrFail($investigationId);
        $booking = $investigation->booking;

        return DB::transaction(function () use ($investigation, $booking, $adminId) {
            $investigation->update(['status' => 'under_investigation']);
            $booking->update(['status' => 'under_investigation']);

            $booking->activities()->create([
                'activity_type'    => 'investigation_escalated',
                'activity_message' => 'Investigation escalated. All funds frozen.',
                'created_by'       => $adminId,
            ]);

            return $investigation->fresh();
        });
    }

    public function resolve(
        int $investigationId,
        int $adminId,
        string $resolution,
        ?float $penaltyAmount = null,
        ?string $notes = null
    ): InvestigationLog {
        $investigation = InvestigationLog::findOrFail($investigationId);
        $booking = $investigation->booking;

        return DB::transaction(function () use ($investigation, $booking, $adminId, $resolution, $penaltyAmount, $notes) {
            match ($resolution) {
                'released_to_provider' => $this->escrowService->release($booking, $adminId),
                'refunded_to_customer' => $this->escrowService->refund($booking, $adminId),
                'partial_refund'       => $this->resolvePartialRefund($booking, $adminId, $penaltyAmount),
                'penalty_deducted'     => $this->resolvePenalty($booking, $adminId, $penaltyAmount),
                'dismissed'            => $this->escrowService->release($booking, $adminId),
                default                => throw new \InvalidArgumentException("Invalid resolution: {$resolution}"),
            };

            $updateData = [
                'status'           => 'resolved',
                'resolution'       => $resolution,
                'resolved_by'      => $adminId,
                'resolved_at'      => now(),
                'resolution_notes' => $notes,
            ];

            if ($resolution === 'penalty_deducted' && $penaltyAmount) {
                $updateData['penalty_amount'] = $penaltyAmount;
            }

            $investigation->update($updateData);

            if ($booking->provider) {
                $this->insuranceService->unfreeze($booking->provider);
            }

            $booking->status = 'resolved';
            $booking->investigation_notes = $notes;
            $booking->save();

            $booking->activities()->create([
                'activity_type'    => 'investigation_resolved',
                'activity_message' => "Investigation resolved: {$resolution}",
                'created_by'       => $adminId,
            ]);

            return $investigation->fresh();
        });
    }

    private function resolvePartialRefund(Booking $booking, int $adminId, ?float $amount): void
    {
        $this->escrowService->deductPenalty($booking, $amount ?? 0, $adminId, 'Partial refund penalty');
        $this->escrowService->release($booking, $adminId);
    }

    private function resolvePenalty(Booking $booking, int $adminId, ?float $amount): void
    {
        if ($booking->provider && $amount) {
            $this->insuranceService->deductPenalty(
                $booking->provider,
                $amount,
                "Penalty for dispute on booking #{$booking->id}",
                $adminId
            );
        }
        $this->escrowService->refund($booking, $adminId);
    }

    public function getDashboardStats(): array
    {
        return [
            'active_investigations' => InvestigationLog::whereIn('status', ['open', 'under_investigation'])->count(),
            'pending_disputes'      => Booking::where('status', 'disputed')->count(),
            'resolved_this_month'   => InvestigationLog::where('status', 'resolved')
                ->whereMonth('resolved_at', now()->month)->count(),
            'total_penalties'       => InvestigationLog::sum('penalty_amount'),
        ];
    }
}
