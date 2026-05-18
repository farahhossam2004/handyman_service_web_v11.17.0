<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentHistory;
use App\Models\BookingActivity;
use App\Models\BookingStatus;
use App\Models\User;
use App\Models\Quote;
use App\Services\CommissionService;
use App\Services\EscrowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BookingWorkflowService
 *
 * Owns the state machine for the inspection-based booking workflow:
 *
 *   pending_inspection
 *     └─ markInspected()       → waiting_quote
 *
 *   waiting_quote | quote_rejected
 *     └─ submitQuote()         → quoted
 *
 *   quoted
 *     ├─ approveQuote()        → quote_approved
 *     └─ rejectQuote()         → quote_rejected
 *
 *   quote_approved  (+ payment_status = escrow)
 *     └─ startBooking()        → in_progress
 *
 *   in_progress
 *     └─ completeBooking()     → completed + payment_status = released
 *
 * All public methods return an array:
 *   ['ok' => bool, 'code' => int, 'message' => string, 'data' => array]
 */
class BookingWorkflowService
{
    // =========================================================================
    // TRANSITIONS
    // =========================================================================

    /**
     * Provider marks a booking as inspected.
     * Transition: pending_inspection → waiting_quote
     */
    public function markInspected(Booking $booking, int $actorId): array
    {
        if ($booking->provider_id !== $actorId) {
            return $this->forbidden('Only the assigned provider can mark this booking as inspected.');
        }

        if ($booking->status !== 'pending_inspection') {
            return $this->invalidTransition(
                'mark-inspected',
                'pending_inspection',
                $booking->status
            );
        }

        $old = $booking->status;
        $booking->status = 'waiting_quote';
        $booking->save();

        return $this->ok('Booking marked as inspected. Awaiting quote.', [
            'booking_id' => $booking->id,
            'status'     => $booking->status,
            'old_status' => $old,
        ]);
    }

    /**
     * Provider submits (or re-submits after rejection) a price quote.
     * Transition: waiting_quote|quote_rejected → quoted
     */
    public function submitQuote(Booking $booking, int $actorId, float $price, ?string $description): array
    {
        if ($booking->provider_id !== $actorId) {
            return $this->forbidden('Only the assigned provider can submit a quote.');
        }

        $allowed = ['waiting_quote', 'quote_rejected'];
        if (! in_array($booking->status, $allowed)) {
            return $this->invalidTransition(
                'submit-quote',
                implode(' or ', $allowed),
                $booking->status
            );
        }

        return DB::transaction(function () use ($booking, $actorId, $price, $description) {
            // Create a Quote record for history
            $quote = Quote::create([
                'booking_id'  => $booking->id,
                'provider_id' => $actorId,
                'price'       => $price,
                'notes'       => $description,
                'status'      => 'pending',
            ]);

            // Update booking — store quote fields directly for easy Flutter access
            $old               = $booking->status;
            $booking->status            = 'quoted';
            $booking->quote_id          = $quote->id;
            $booking->quote_price       = $price;
            $booking->quote_description = $description;
            $booking->total_amount      = $price; // align total with quoted price
            $booking->save();

            return $this->ok('Quote submitted successfully.', [
                'booking_id'        => $booking->id,
                'status'            => $booking->status,
                'old_status'        => $old,
                'quote_id'          => $quote->id,
                'quote_price'       => $booking->quote_price,
                'quote_description' => $booking->quote_description,
            ]);
        });
    }

    /**
     * Customer approves the provider's quote.
     * Transition: quoted → quote_approved
     */
    public function approveQuote(Booking $booking, int $actorId): array
    {
        if ($booking->customer_id !== $actorId) {
            return $this->forbidden('Only the booking customer can approve a quote.');
        }

        if ($booking->status !== 'quoted') {
            return $this->invalidTransition('approve-quote', 'quoted', $booking->status);
        }

        return DB::transaction(function () use ($booking) {
            // Mark the Quote record as approved
            if ($booking->quote_id) {
                Quote::where('id', $booking->quote_id)->update(['status' => 'approved']);
            }

            $old = $booking->status;
            $booking->status = 'quote_approved';
            $booking->save();

            return $this->ok('Quote approved. Please proceed with payment.', [
                'booking_id'  => $booking->id,
                'status'      => $booking->status,
                'old_status'  => $old,
                'quote_price' => $booking->quote_price,
            ]);
        });
    }

    /**
     * Customer rejects the provider's quote.
     * Transition: quoted → quote_rejected
     */
    public function rejectQuote(Booking $booking, int $actorId, ?string $reason): array
    {
        if ($booking->customer_id !== $actorId) {
            return $this->forbidden('Only the booking customer can reject a quote.');
        }

        if ($booking->status !== 'quoted') {
            return $this->invalidTransition('reject-quote', 'quoted', $booking->status);
        }

        return DB::transaction(function () use ($booking, $reason) {
            // Mark the Quote record as rejected
            if ($booking->quote_id) {
                Quote::where('id', $booking->quote_id)->update(['status' => 'rejected']);
            }

            $old = $booking->status;
            $booking->status = 'quote_rejected';
            if ($reason) {
                $booking->reason = $reason;
            }
            $booking->save();

            return $this->ok('Quote rejected. The provider may submit a revised quote.', [
                'booking_id' => $booking->id,
                'status'     => $booking->status,
                'old_status' => $old,
            ]);
        });
    }

    /**
     * Place payment in escrow after customer pays.
     * Called from PaymentController::savePayment() when booking is quote_approved.
     *
     * This method does NOT transition the booking status — it only sets
     * payment_status = 'escrow', signalling the money is held.
     */
    public function holdInEscrow(Booking $booking): array
    {
        $allowedBookingStatuses = ['quote_approved'];
        if (! in_array($booking->status, $allowedBookingStatuses)) {
            return $this->error(
                400,
                'Payment can only be made after the quote is approved. Current status: ' . $booking->status
            );
        }

        $booking->payment_status = 'escrow';
        $booking->save();

        return $this->ok('Payment held in escrow.', [
            'booking_id'     => $booking->id,
            'payment_status' => $booking->payment_status,
        ]);
    }

    /**
     * Provider starts the job.
     * Transition: quote_approved (+ escrow payment) → in_progress
     */
    public function startBooking(Booking $booking, int $actorId): array
    {
        if ($booking->provider_id !== $actorId) {
            return $this->forbidden('Only the assigned provider can start the booking.');
        }

        if ($booking->status !== 'quote_approved') {
            return $this->invalidTransition('booking-start', 'quote_approved', $booking->status);
        }

        // Verify payment is held in escrow (on the booking) OR confirmed in payments table
        $paymentOk = $this->isPaymentConfirmed($booking);
        if (! $paymentOk) {
            return $this->error(
                422,
                'Cannot start booking. Payment must be confirmed (escrow) first.'
            );
        }

        $old = $booking->status;
        $booking->status = 'in_progress';
        $booking->save();

        return $this->ok('Booking started successfully.', [
            'booking_id' => $booking->id,
            'status'     => $booking->status,
            'old_status' => $old,
        ]);
    }

    /**
     * Provider (or system) marks a booking as complete and releases escrow.
     * Transition: in_progress → completed + payment_status = released
     */
    public function completeBooking(Booking $booking, int $actorId): array
    {
        if ($booking->provider_id !== $actorId) {
            return $this->forbidden('Only the assigned provider can complete the booking.');
        }

        if ($booking->status !== 'in_progress') {
            return $this->invalidTransition('complete-booking', 'in_progress', $booking->status);
        }

        return DB::transaction(function () use ($booking) {
            $old = $booking->status;
            $booking->status         = 'completed';
            $booking->payment_status = 'released';
            $booking->save();

            $payment = Payment::where('booking_id', $booking->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($payment) {
                $payment->payment_status = 'paid';
                $payment->save();
            }

            $commissionService = app(CommissionService::class);
            $commissionService->apply($booking);

            $escrowService = app(EscrowService::class);
            $escrowService->release($booking, $booking->provider_id);

            return $this->ok('Booking completed and payment released to provider.', [
                'booking_id'     => $booking->id,
                'status'         => $booking->status,
                'old_status'     => $old,
                'payment_status' => $booking->payment_status,
            ]);
        });
    }

    // =========================================================================
    // DISPUTE / INVESTIGATION TRANSITIONS
    // =========================================================================

    /**
     * Customer (or admin) opens a dispute on a booking.
     * Booking must be in a state where payment has been held (escrow).
     * Transition: quote_approved | in_progress | completed | cancelled → disputed
     */
    public function openDispute(Booking $booking, int $actorId, string $reason): array
    {
        if ($booking->customer_id !== $actorId && ! auth()->user()?->hasRole('admin')) {
            return $this->forbidden(__('messages.dispute_forbidden'));
        }

        $allowed = ['quote_approved', 'in_progress', 'completed', 'cancelled'];
        if (! in_array($booking->status, $allowed)) {
            return $this->invalidTransition(
                'open-dispute',
                implode(' or ', $allowed),
                $booking->status
            );
        }

        return DB::transaction(function () use ($booking, $reason) {
            $old = $booking->status;
            $booking->status = 'disputed';
            $booking->payment_status = 'frozen_under_investigation';
            $booking->dispute_reason = $reason;
            $booking->save();

            return $this->ok(__('messages.dispute_opened'), [
                'booking_id'     => $booking->id,
                'status'         => $booking->status,
                'old_status'     => $old,
                'payment_status' => $booking->payment_status,
                'dispute_reason' => $reason,
            ]);
        });
    }

    /**
     * Admin escalates a dispute to a formal investigation.
     * Transition: disputed → under_investigation
     */
    public function escalateToInvestigation(Booking $booking, int $actorId, ?string $notes): array
    {
        if ($booking->status !== 'disputed') {
            return $this->invalidTransition('escalate-investigation', 'disputed', $booking->status);
        }

        return DB::transaction(function () use ($booking, $actorId, $notes) {
            $old = $booking->status;
            $booking->status = 'under_investigation';
            $booking->investigation_notes = $notes;
            $booking->investigated_by = $actorId;
            $booking->save();

            return $this->ok(__('messages.investigation_started'), [
                'booking_id' => $booking->id,
                'status'     => $booking->status,
                'old_status' => $old,
            ]);
        });
    }

    /**
     * Admin resolves an investigation and chooses the financial outcome.
     * Transition: under_investigation → resolved
     *
     * Resolution types:
     *   - released_to_provider   : escrow released
     *   - refunded_to_customer   : escrow refunded
     *   - partial_refund         : split escrow
     *   - penalty_deducted       : penalty from provider insurance
     *   - dismissed              : no action, escrow returned to customer
     */
    public function resolveInvestigation(Booking $booking, int $actorId, string $resolution, ?array $meta): array
    {
        if ($booking->status !== 'under_investigation') {
            return $this->invalidTransition('resolve-investigation', 'under_investigation', $booking->status);
        }

        $allowedResolutions = [
            'released_to_provider',
            'refunded_to_customer',
            'partial_refund',
            'penalty_deducted',
            'dismissed',
        ];
        if (! in_array($resolution, $allowedResolutions)) {
            return $this->error(422, __('messages.invalid_resolution_type') . " [{$resolution}].");
        }

        return DB::transaction(function () use ($booking, $resolution, $meta) {
            $old = $booking->status;
            $booking->status = 'resolved';
            $booking->investigation_notes = ($meta['notes'] ?? null) ?: $booking->investigation_notes;

            switch ($resolution) {
                case 'released_to_provider':
                    $booking->payment_status = 'released';
                    break;
                case 'refunded_to_customer':
                    $booking->payment_status = 'refunded';
                    break;
                case 'partial_refund':
                    $booking->payment_status = 'partially_released';
                    break;
                case 'penalty_deducted':
                    $booking->payment_status = 'released';
                    break;
                case 'dismissed':
                    $booking->payment_status = 'released';
                    break;
            }

            $booking->save();

            return $this->ok(__('messages.investigation_resolved'), [
                'booking_id'     => $booking->id,
                'status'         => $booking->status,
                'old_status'     => $old,
                'payment_status' => $booking->payment_status,
                'resolution'     => $resolution,
            ]);
        });
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Check whether the payment associated with this booking is confirmed. */
    public function isPaymentConfirmed(Booking $booking): bool
    {
        // Check on the booking row itself first (escrow field)
        if (in_array($booking->payment_status, ['escrow', 'paid', 'pending_release'])) {
            return true;
        }

        // Fall back to the payments table (covers legacy payment flows)
        $payment = Payment::where('booking_id', $booking->id)
            ->orderBy('id', 'desc')
            ->first();

        if (! $payment) {
            return false;
        }

        return in_array($payment->payment_status, ['paid', 'advanced_paid', 'held', 'escrow']);
    }

    // ── Response builders ─────────────────────────────────────────────────────

    private function ok(string $message, array $data = []): array
    {
        return ['ok' => true, 'code' => 200, 'message' => $message, 'data' => $data];
    }

    private function forbidden(string $message): array
    {
        return ['ok' => false, 'code' => 403, 'message' => $message, 'data' => []];
    }

    private function error(int $code, string $message): array
    {
        return ['ok' => false, 'code' => $code, 'message' => $message, 'data' => []];
    }

    private function invalidTransition(string $action, string $required, string $current): array
    {
        return $this->error(
            422,
            "Action [{$action}] requires status [{$required}], but booking is currently [{$current}]."
        );
    }
}
