<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingWorkflowService;
use App\Traits\NotificationTrait;
use App\Traits\EarningTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * QuoteController – Inspection → Quote → Approval → Payment workflow
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │  Routes (all under auth:sanctum)                                         │
 * │                                                                          │
 * │  POST  api/mark-inspected    → markInspected()   provider only           │
 * │  POST  api/add-quote         → addQuote()         provider only           │
 * │  POST  api/submit-quote      → addQuote()         alias                  │
 * │  POST  api/approve-quote     → approveQuote()     user (customer) only   │
 * │  POST  api/reject-quote      → rejectQuote()      user (customer) only   │
 * │  POST  api/booking-start     → startBooking()     provider only          │
 * │  POST  api/complete-booking  → completeBooking()  provider only          │
 * │  GET   api/booking-quote     → getQuote()         both                   │
 * └──────────────────────────────────────────────────────────────────────────┘
 *
 * All responses conform to:
 *   Success: { "status": true,  "message": "...", "data": {...} }
 *   Error:   { "status": false, "message": "..."                }
 */
class QuoteController extends Controller
{
    use NotificationTrait;
    use EarningTrait;

    public function __construct(
        private readonly BookingWorkflowService $workflow
    ) {}

    // =========================================================================
    // PROVIDER ACTIONS
    // =========================================================================

    /**
     * POST api/mark-inspected
     *
     * Provider visited the site and inspected the job.
     * pending_inspection → waiting_quote
     *
     * Body: { booking_id }
     */
    public function markInspected(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->markInspected($booking, auth()->id());

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Notify relevant parties
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    /**
     * POST api/add-quote  (also aliased as POST api/submit-quote)
     *
     * Provider adds a price quote after inspection.
     * waiting_quote | quote_rejected → quoted
     *
     * Body: { booking_id, price, description? }
     */
    public function addQuote(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id'  => 'required|integer|exists:bookings,id',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->submitQuote(
            $booking,
            auth()->id(),
            (float) $request->price,
            $request->description
        );

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Notify customer that a quote has been submitted
        $booking->refresh();
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    // =========================================================================
    // USER (CUSTOMER) ACTIONS
    // =========================================================================

    /**
     * POST api/approve-quote
     *
     * User approves the provider's quote → quote_approved
     * User can now proceed to payment.
     *
     * Body: { booking_id }
     */
    public function approveQuote(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->approveQuote($booking, auth()->id());

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Notify provider that quote was approved
        $booking->refresh();
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    /**
     * POST api/reject-quote
     *
     * User rejects the provider's quote → quote_rejected
     * Provider may re-submit a revised quote via add-quote.
     *
     * Body: { booking_id, reason? }
     */
    public function rejectQuote(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'reason'     => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->rejectQuote($booking, auth()->id(), $request->reason);

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Notify provider that quote was rejected
        $booking->refresh();
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    // =========================================================================
    // SHARED
    // =========================================================================

    /**
     * GET api/booking-quote?booking_id=X
     *
     * Returns the full quote details for a booking.
     * Both the customer and the provider can call this.
     */
    public function getQuote(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::with(['quote', 'quotes'])->findOrFail($request->booking_id);
        $user    = auth()->user();

        $isAuthorized = $user->id === $booking->customer_id
            || $user->id === $booking->provider_id
            || $user->hasAnyRole(['admin', 'demo_admin']);

        if (! $isAuthorized) {
            return $this->errorResponse('Unauthorised.', 403);
        }

        return $this->successResponse('Quote details retrieved.', [
            'booking_id'        => $booking->id,
            'booking_status'    => $booking->status,
            'payment_status'    => $booking->payment_status,
            'quote_price'       => $booking->quote_price,
            'quote_description' => $booking->quote_description,
            'active_quote'      => $booking->quote,
            'all_quotes'        => $booking->quotes,
        ]);
    }

    /**
     * POST api/booking-start
     *
     * Provider starts the job after payment is confirmed.
     * quote_approved (+ payment in escrow) → in_progress
     *
     * Body: { booking_id }
     */
    public function startBooking(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->startBooking($booking, auth()->id());

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Notify customer that provider has started the job
        $booking->refresh();
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    /**
     * POST api/complete-booking
     *
     * Provider marks the booking as complete.
     * in_progress → completed + payment_status = released (escrow released)
     *
     * Body: { booking_id }
     */
    public function completeBooking(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);
        if ($v->fails()) {
            return $this->errorResponse($v->errors()->first(), 400);
        }

        $booking = Booking::findOrFail($request->booking_id);
        $result  = $this->workflow->completeBooking($booking, auth()->id());

        if (! $result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        // Trigger commission calculation (same hook as existing bookingUpdate)
        if (method_exists($this, 'addBookingCommission')) {
            $this->addBookingCommission($booking->fresh());
        }

        // Notify both customer and provider
        $booking->refresh();
        $booking->old_status = $result['data']['old_status'];
        $this->sendNotification([
            'activity_type' => 'update_booking_status',
            'booking_id'    => $booking->id,
            'booking'       => $booking,
        ]);

        return $this->successResponse($result['message'], $result['data']);
    }

    // =========================================================================
    // RESPONSE HELPERS
    // =========================================================================

    /** Standard success envelope. */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], 200);
    }

    /** Standard error envelope. */
    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
        ], $code);
    }
}
