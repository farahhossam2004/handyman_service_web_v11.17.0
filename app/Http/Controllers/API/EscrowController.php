<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EscrowService;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EscrowController extends Controller
{
    public function __construct(
        protected EscrowService $escrowService,
    ) {}

    public function status(int $bookingId): JsonResponse
    {
        $booking = Booking::with('escrow')->findOrFail($bookingId);

        return response()->json([
            'status' => 'true',
            'data'   => [
                'booking_id'      => $booking->id,
                'payment_status'  => $booking->payment_status,
                'escrow'          => $booking->escrow ? [
                    'amount'       => (float) $booking->escrow->amount,
                    'held_amount'  => (float) $booking->escrow->held_amount,
                    'status'       => $booking->escrow->status,
                    'held_at'      => $booking->escrow->held_at,
                    'released_at'  => $booking->escrow->released_at,
                ] : null,
            ],
        ]);
    }

    public function myHistory(Request $request): JsonResponse
    {
        $user = auth()->user();

        $transactions = $this->escrowService->getUserHistory($user->id);

        return response()->json([
            'status' => 'true',
            'data'   => $transactions,
        ]);
    }
}
