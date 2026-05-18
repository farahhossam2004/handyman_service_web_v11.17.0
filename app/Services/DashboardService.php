<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\Quote;
use App\Models\EscrowTransaction;
use App\Models\InvestigationLog;
use App\Models\CommissionEarning;
use App\Models\FinancialLedger;
use App\Models\AdminActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        protected EscrowService $escrowService,
        protected InsuranceService $insuranceService,
        protected InvestigationService $investigationService,
    ) {}

    public function getAdminMetrics(): array
    {
        return Cache::remember('sand.admin.metrics', 300, function () {
            $escrowStats = $this->escrowService->getDashboardStats();
            $investigationStats = $this->investigationService->getDashboardStats();

            return [
                'inspection_requests'  => Booking::whereIn('status', ['pending_inspection', 'inspection_requested'])->count(),
                'pending_quotes'       => Quote::pending()->count(),
                'approved_quotes'      => Quote::approved()->count(),
                'active_jobs'          => Booking::where('status', 'in_progress')->count(),
                'disputes'             => Booking::where('status', 'disputed')->count(),
                'held_payments'        => $escrowStats['total_held'],
                'released_payments'    => $escrowStats['total_released'],
                'total_refunded'       => $escrowStats['total_refunded'],
                'frozen_funds'         => $escrowStats['total_frozen'],
                'total_penalties'      => $escrowStats['total_penalties'],
                'escrow_count_active'  => $escrowStats['count_active'],
                'active_providers'     => User::where('user_type', 'provider')->where('status', 1)->count(),
                'active_handymen'      => User::where('user_type', 'handyman')->where('status', 1)->count(),
                'active_customers'     => User::where('user_type', 'user')->where('status', 1)->count(),
                'insurance_active'     => User::where('insurance_status', 'active')->count(),
                'insurance_pending'    => User::whereIn('insurance_status', ['unpaid', 'partial'])->count(),
                'insurance_frozen'     => User::where('insurance_status', 'frozen')->count(),
                'insurance_total_held' => User::sum('insurance_balance'),
                'active_investigations' => $investigationStats['active_investigations'],
                'resolved_investigations' => $investigationStats['resolved_this_month'],
                'daily_revenue'    => CommissionEarning::whereDate('created_at', today())->sum('commission_amount'),
                'monthly_revenue'  => CommissionEarning::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)->sum('commission_amount'),
                'total_revenue'    => CommissionEarning::sum('commission_amount'),
                'ledger_entries'   => FinancialLedger::count(),
                'admin_actions'    => AdminActivityLog::whereDate('created_at', today())->count(),
            ];
        });
    }

    public function getRevenueTrend(): array
    {
        return Cache::remember('sand.admin.revenue_trend', 600, function () {
            $monthly = CommissionEarning::select(
                DB::raw('SUM(commission_amount) as total'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();

        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $found = $monthly->firstWhere(fn($item) => $item->month == $date->month && $item->year == $date->year);
            $data[] = $found ? (float) $found->total : 0;
        }

        return ['labels' => $labels, 'data' => $data];
        });
    }

    public function getBookingFunnel(): array
    {
        return [
            ['stage' => 'Inspections',  'count' => Booking::whereIn('status', ['pending_inspection', 'inspected'])->count()],
            ['stage' => 'Quotes',       'count' => Booking::whereIn('status', ['quoted', 'quote_approved'])->count()],
            ['stage' => 'In Progress',  'count' => Booking::where('status', 'in_progress')->count()],
            ['stage' => 'Completed',    'count' => Booking::where('status', 'completed')->count()],
            ['stage' => 'Disputed',     'count' => Booking::whereIn('status', ['disputed', 'under_investigation'])->count()],
        ];
    }

    public function getEscrowTrend(): array
    {
        $daily = EscrowTransaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(held_amount) as held'),
            DB::raw('SUM(released_amount) as released'),
        )
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')->orderBy('date')
        ->get()->keyBy('date');

        $labels = [];
        $held = [];
        $released = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            $held[] = isset($daily[$date]) ? (float) $daily[$date]->held : 0;
            $released[] = isset($daily[$date]) ? (float) $daily[$date]->released : 0;
        }

        return ['labels' => $labels, 'series' => [
            ['name' => 'Held',     'data' => $held],
            ['name' => 'Released', 'data' => $released],
        ]];
    }

    public function getProviderMetrics(int $providerId): array
    {
        $escrowStats = $this->escrowService->getDashboardStats();

        return [
            'inspection_requests'  => Booking::where('provider_id', $providerId)
                ->whereIn('status', ['pending_inspection', 'inspection_requested'])->count(),
            'pending_quotes'       => Quote::where('provider_id', $providerId)->pending()->count(),
            'approved_quotes'      => Quote::where('provider_id', $providerId)->approved()->count(),
            'active_jobs'          => Booking::where('provider_id', $providerId)
                ->where('status', 'in_progress')->count(),
            'completed_jobs'       => Booking::where('provider_id', $providerId)
                ->where('status', 'completed')->count(),
            'disputes'             => Booking::where('provider_id', $providerId)
                ->where('status', 'disputed')->count(),
            'total_earned'         => Booking::where('provider_id', $providerId)
                ->where('payment_status', 'released')->sum('total_amount'),
            'escrow_balance'       => Booking::where('provider_id', $providerId)
                ->where('payment_status', 'escrow')->sum('total_amount'),
            'insurance_balance'    => User::where('id', $providerId)->value('insurance_balance') ?? 0,
            'insurance_status'     => User::where('id', $providerId)->value('insurance_status') ?? 'unpaid',
        ];
    }

    public function getInsuranceStats(): array
    {
        return [
            'active'  => User::where('insurance_status', 'active')->count(),
            'pending' => User::whereIn('insurance_status', ['unpaid', 'partial'])->count(),
            'frozen'  => User::where('insurance_status', 'frozen')->count(),
            'refunded' => User::where('insurance_status', 'refunded')->count(),
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('sand.admin.metrics');
        Cache::forget('sand.admin.revenue_trend');
        Cache::forget('sand.admin.booking_funnel');
        Cache::forget('sand.admin.escrow_trend');
    }
}
