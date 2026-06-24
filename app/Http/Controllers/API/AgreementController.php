<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AgreementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgreementController extends Controller
{
    public function __construct(
        protected AgreementService $agreementService,
    ) {}

    public function show(string $type): JsonResponse
    {
        $agreement = $this->agreementService->getActiveAgreement($type);
        if (! $agreement) {
            return response()->json(['status' => 'false', 'message' => __('messages.agreement_not_found')], 404);
        }

        $user = auth()->user();

        return response()->json([
            'status' => 'true',
            'data'   => [
                'id'        => $agreement->id,
                'type'      => $agreement->type,
                'content'   => $agreement->content_ar,
                'content_en'=> $agreement->content_en,
                'version'   => $agreement->version,
                'accepted'  => $user ? $this->agreementService->hasAcceptedLatest($user, $type) : false,
            ],
        ]);
    }

    public function accept(string $type, Request $request): JsonResponse
    {
        $user = auth()->user();
        $alreadyAccepted = $this->agreementService->hasAcceptedLatest($user, $type);

        if ($alreadyAccepted) {
            return response()->json([
                'status'  => 'true',
                'message' => __('messages.agreement_already_accepted'),
            ]);
        }

        $this->agreementService->accept(
            $user,
            $type,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'status'  => 'true',
            'message' => __('messages.agreement_accepted_successfully'),
        ]);
    }
}
