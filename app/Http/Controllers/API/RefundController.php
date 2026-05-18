<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\InvestigationLog;
use App\Services\EscrowService;
use App\Services\FinancialLedgerService;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function __construct(
        protected EscrowService $escrowService,
        protected FinancialLedgerService $ledger,
        protected AdminActivityLogService $adminLog,
    ) {}

    public function process(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount'     => 'nullable|numeric|min:0.01',
            'reason'     => 'required|string|max:2000',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $adminId = auth()->id();

        $allowedStatuses = ['cancelled', 'disputed', 'resolved'];
        if (! in_array($booking->status, $allowedStatuses)) {
            return response()->json([
                'status'  => 'false',
                'message' => "Cannot refund booking in status [{$booking->status}]. Allowed: cancelled, disputed, resolved.",
            ], 422);
        }

        if ($booking->payment_status === 'refunded') {
            return response()->json([
                'status'  => 'false',
                'message' => 'Booking already refunded.',
            ], 422);
        }

        $escrow = \App\Models\EscrowTransaction::where('escrowable_id', $booking->id)
            ->where('escrowable_type', Booking::class)
            ->whereIn('status', ['held', 'frozen_under_investigation', 'partially_released'])
            ->latest()
            ->first();

        if (! $escrow) {
            return response()->json([
                'status'  => 'false',
                'message' => 'No active escrow found for this booking.',
            ], 404);
        }

        $refundAmount = $validated['amount'] ?? $escrow->held_amount;
        if ($refundAmount > $escrow->held_amount) {
            return response()->json([
                'status'  => 'false',
                'message' => "Refund amount ({$refundAmount} SAR) exceeds held amount ({$escrow->held_amount} SAR).",
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($escrow, $booking, $adminId, $refundAmount, $validated) {
                $remaining = $escrow->held_amount - $refundAmount;

                $escrow->update([
                    'held_amount'      => $remaining,
                    'refunded_amount'  => $escrow->refunded_amount + $refundAmount,
                    'notes'            => "Partial refund of {$refundAmount} SAR. Reason: {$validated['reason']}",
                    'actioned_by'      => $adminId,
                ]);

                if ($remaining <= 0) {
                    $escrow->update([
                        'status'      => 'refunded',
                        'refunded_at' => now(),
                    ]);
                    $booking->payment_status = 'refunded';
                } else {
                    $booking->payment_status = 'partially_released';
                }
                $booking->save();

                $customerWallet = $booking->customer->wallet
                    ?? \App\Models\Wallet::create(['user_id' => $booking->customer_id, 'amount' => 0]);
                $balanceBefore = (float) $customerWallet->amount;
                $customerWallet->increment('amount', $refundAmount);
                $customerWallet->syncBalances();

                $this->ledger->recordRefund($booking->customer, $refundAmount, $balanceBefore, $booking->id,
                    "refund_{$booking->id}_" . now()->timestamp);

                $this->adminLog->logRefund($adminId, $booking->customer_id, $booking->id, $refundAmount);

                return [
                    'booking_id'      => $booking->id,
                    'refund_amount'   => $refundAmount,
                    'remaining_escrow' => $remaining,
                    'payment_status'  => $booking->payment_status,
                ];
            });

            return response()->json([
                'status'  => 'true',
                'message' => "Refund of {$refundAmount} SAR processed successfully.",
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'false',
                'message' => 'Refund failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
