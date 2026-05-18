<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FailedTransactionQueue;
use App\Models\ReconciliationReport;
use App\Models\FinancialTraceLog;
use App\Services\FailedTransactionService;
use App\Services\FinancialReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinancialAdminController extends Controller
{
    public function __construct(
        protected FailedTransactionService $failedService,
        protected FinancialReconciliationService $reconciliation,
    ) {}

    public function failedTransactions(Request $request): JsonResponse
    {
        $status = $request->status ?? 'pending_retry';
        $items = FailedTransactionQueue::where('status', $status)
            ->with('operable', 'createdBy')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'true',
            'data'   => $items,
        ]);
    }

    public function retryFailed(int $id): JsonResponse
    {
        $failed = FailedTransactionQueue::findOrFail($id);
        $result = $this->failedService->retry($failed);

        return response()->json([
            'status'  => $result['success'] ? 'true' : 'false',
            'message' => $result['message'],
            'data'    => $failed->fresh(),
        ]);
    }

    public function reconciliationReport(): JsonResponse
    {
        $report = $this->reconciliation->getLatestReport();

        return response()->json([
            'status' => 'true',
            'data'   => $report,
        ]);
    }

    public function reconciliationHistory(Request $request): JsonResponse
    {
        $history = $this->reconciliation->getReportHistory($request->days ?? 30);

        return response()->json([
            'status' => 'true',
            'data'   => $history,
        ]);
    }

    public function runReconciliation(): JsonResponse
    {
        try {
            $report = $this->reconciliation->run();

            return response()->json([
                'status' => 'true',
                'data'   => $report,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'false',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function traceLog(Request $request): JsonResponse
    {
        $query = FinancialTraceLog::query();

        if ($request->trace_id) {
            $query->where('financial_trace_id', $request->trace_id);
        }
        if ($request->operation_type) {
            $query->where('operation_type', $request->operation_type);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'status' => 'true',
            'data'   => $logs,
        ]);
    }
}
