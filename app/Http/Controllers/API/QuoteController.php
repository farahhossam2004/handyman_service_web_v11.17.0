<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Quote;
use App\Services\BookingWorkflowService;
use App\Services\AgreementService;
use App\Http\Resources\API\QuoteResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function __construct(
        protected BookingWorkflowService $workflowService,
        protected AgreementService $agreementService,
    ) {}

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id'        => 'required|exists:bookings,id',
            'price'             => 'required|numeric|min:0',
            'estimated_duration'=> 'nullable|integer|min:1',
            'notes'             => 'nullable|string|max:2000',
            'description'       => 'nullable|string|max:2000',
            'inspection_notes'  => 'nullable|string|max:5000',
            'handyman_id'       => 'nullable|exists:users,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $description = $validated['notes'] ?? $validated['description'] ?? null;

        $result = $this->workflowService->submitQuote(
            $booking,
            $actorId,
            $validated['price'],
            $description,
        );

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        $quote = Quote::find($booking->quote_id);
        if ($quote) {
            $quote->update([
                'estimated_duration' => $validated['estimated_duration'],
                'inspection_notes'   => $validated['inspection_notes'],
                'handyman_id'        => $validated['handyman_id'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.quote_submitted'),
            'data'    => new QuoteResource($quote ?? $booking),
        ]);
    }

    public function history(int $bookingId): JsonResponse
    {
        $quotes = Quote::where('booking_id', $bookingId)
            ->with(['provider:id,display_name', 'handyman:id,display_name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => QuoteResource::collection($quotes),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $quote = Quote::with(['booking', 'provider', 'handyman'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => new QuoteResource($quote),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $quotes = Quote::with([
            'booking:id,customer_id,status,quote_price,created_at',
            'booking.customer:id,display_name,phone_number',
            'provider:id,display_name,phone_number',
            'handyman:id,display_name',
        ])
        ->when($request->status, fn($q, $s) => $q->where('status', $s))
        ->when($request->provider_id, fn($q, $id) => $q->where('provider_id', $id))
        ->orderBy('created_at', 'desc')
        ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => true,
            'data'   => QuoteResource::collection($quotes),
        ]);
    }

    public function markInspected(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $result = $this->workflowService->markInspected($booking, $actorId);

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.booking_marked_as_inspected'),
            'data'    => $result['data'],
        ]);
    }

    public function addQuote(Request $request): JsonResponse
    {
        return $this->submit($request);
    }

    public function startBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $result = $this->workflowService->startBooking($booking, $actorId);

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.booking_started'),
            'data'    => $result['data'],
        ]);
    }

    public function approveQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $result = $this->workflowService->approveQuote($booking, $actorId);

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.quote_approved'),
            'data'    => $result['data'],
        ]);
    }

    public function rejectQuote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'reason'     => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $actorId = auth()->id();

        $result = $this->workflowService->rejectQuote($booking, $actorId, $validated['reason'] ?? null);

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.quote_rejected'),
            'data'    => $result['data'],
        ]);
    }

    public function getQuote(Request $request): JsonResponse
    {
        $bookingId = $request->booking_id ?? $request->id;

        $booking = Booking::with(['quote', 'customer', 'provider'])
            ->findOrFail($bookingId);

        if (! $booking->quote) {
            return response()->json([
                'status'  => false,
                'message' => 'No quote found for this booking',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new QuoteResource($booking->quote),
        ]);
    }
}
