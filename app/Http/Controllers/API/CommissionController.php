<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;

class CommissionController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService,
    ) {}

    public function details(): JsonResponse
    {
        $providerId = auth()->id();
        $details = $this->commissionService->getProviderCommissionDetails($providerId);

        return response()->json([
            'status' => 'true',
            'data'   => $details,
        ]);
    }

    public function adminOverview(): JsonResponse
    {
        $overview = $this->commissionService->getAdminFinancialOverview();

        return response()->json([
            'status' => 'true',
            'data'   => $overview,
        ]);
    }
}
