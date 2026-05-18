<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\InvestigationService;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvestigationController extends Controller
{
    public function __construct(
        protected InvestigationService $investigationService,
    ) {}

    public function createDispute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'reason'     => 'required|string|max:5000',
            'evidence'   => 'nullable|array',
            'evidence.*' => 'nullable|string|max:2048',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $workflowService = app(\App\Services\BookingWorkflowService::class);
        $result = $workflowService->openDispute($booking, auth()->id(), $validated['reason']);

        if (! $result['ok']) {
            return response()->json($result, $result['code']);
        }

        $investigation = $this->investigationService->open(
            $booking,
            auth()->id(),
            $validated['reason'],
        );

        if (! empty($validated['evidence'])) {
            foreach ($validated['evidence'] as $file) {
                $investigation->activities()->create([
                    'user_id'  => auth()->id(),
                    'action'   => 'evidence_uploaded',
                    'evidence' => json_encode([$file]),
                ]);
            }
        }

        return response()->json([
            'status'  => 'true',
            'message' => __('messages.dispute_opened'),
            'data'    => $investigation->load('activities'),
        ]);
    }

    public function listDisputes(Request $request): JsonResponse
    {
        $user = auth()->user();
        $disputes = \App\Models\InvestigationLog::with(['booking', 'openedBy', 'activities'])
            ->where(function ($q) use ($user) {
                if ($user->user_type === 'provider') {
                    $q->where('provider_id', $user->id);
                } elseif ($user->user_type === 'user') {
                    $q->whereHas('booking', fn($b) => $b->where('customer_id', $user->id));
                }
            })
            ->orWhere('opened_by', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'true',
            'data'   => $disputes,
        ]);
    }

    public function show(int $bookingId): JsonResponse
    {
        $booking = Booking::with('investigation.activities.user')->findOrFail($bookingId);

        if (! $booking->investigation) {
            return response()->json([
                'status' => 'false',
                'message' => __('messages.no_investigation_found'),
            ], 404);
        }

        return response()->json([
            'status' => 'true',
            'data'   => $booking->investigation->load('openedBy', 'resolvedBy', 'activities'),
        ]);
    }

    public function respond(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $investigation = \App\Models\InvestigationLog::findOrFail($id);

        $investigation->activities()->create([
            'user_id'     => auth()->id(),
            'action'      => 'responded',
            'description' => $validated['message'],
        ]);

        return response()->json([
            'status'  => 'true',
            'message' => __('messages.response_added'),
        ]);
    }
}
