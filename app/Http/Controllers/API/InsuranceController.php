<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\InsuranceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InsuranceController extends Controller
{
    public function __construct(
        protected InsuranceService $insuranceService,
    ) {}

    public function status(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'status' => 'true',
            'data'   => [
                'balance'           => (float) $user->insurance_balance,
                'target'            => (float) $user->insurance_target,
                'status'            => $user->insurance_status,
                'frozen_amount'     => (float) $user->frozen_amount,
                'is_covered'        => $this->insuranceService->isCovered($user),
                'withdrawable'      => $this->insuranceService->getWithdrawableBalance($user),
                'config'            => $this->insuranceService->getConfig(),
            ],
        ]);
    }

    public function deposit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = auth()->user();
        $transaction = $this->insuranceService->deposit($user, $validated['amount'], $user->id);

        return response()->json([
            'status'  => 'true',
            'message' => __('messages.insurance_deposit_successful'),
            'data'    => $transaction,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $transactions = \App\Models\InsuranceTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'true',
            'data'   => $transactions,
        ]);
    }
}
