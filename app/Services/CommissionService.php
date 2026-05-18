<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CommissionEarning;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    const DEFAULT_RATE = 10;
    const PROMO_ORDERS = 5;

    public function __construct(
        protected InsuranceService $insuranceService,
        protected FinancialLedgerService $ledger,
    ) {}

    public function calculate(Booking $booking): array
    {
        $provider = $booking->provider;
        $completedCount = $this->getCompletedOrderCount($provider->id);
        $isPromo = $completedCount < self::PROMO_ORDERS;

        $rate = $isPromo ? 0 : self::DEFAULT_RATE;
        $totalAmount = (float) ($booking->total_amount ?? $booking->quote_price ?? 0);
        $commissionAmount = round($totalAmount * ($rate / 100), 2);

        return [
            'is_commission_free' => $isPromo,
            'commission_rate'    => $rate,
            'total_amount'       => $totalAmount,
            'commission_amount'  => $commissionAmount,
            'provider_earns'     => $totalAmount - $commissionAmount,
            'completed_orders'   => $completedCount,
            'remaining_free'     => max(0, self::PROMO_ORDERS - $completedCount),
        ];
    }

    public function apply(Booking $booking): CommissionEarning
    {
        $details = $this->calculate($booking);

        return DB::transaction(function () use ($booking, $details) {
            $earning = CommissionEarning::create([
                'employee_id'       => $booking->provider_id,
                'booking_id'        => $booking->id,
                'commission_amount' => $details['commission_amount'],
                'user_type'         => 'provider',
                'commission_status' => $details['is_commission_free'] ? 'promo' : 'pending',
                'commissions'       => json_encode($details),
                'payment_date'      => now()->toDateString(),
            ]);

            if (! $details['is_commission_free'] && $details['commission_amount'] > 0) {
                $this->deductFromProvider($booking->provider, $details['commission_amount'], $booking->id);
            }

            $this->insuranceService->deductGradually($booking->provider, $details['provider_earns']);

            return $earning;
        });
    }

    protected function getCompletedOrderCount(int $providerId): int
    {
        return Booking::where('provider_id', $providerId)
            ->whereIn('status', ['completed', 'released'])
            ->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhere('payment_status', '!=', 'refunded');
            })
            ->count();
    }

    protected function deductFromProvider(User $provider, float $amount, int $bookingId): void
    {
        $wallet = $provider->wallet;
        if (! $wallet) return;

        $this->guardSufficientBalance($wallet, $amount);

        $balanceBefore = (float) $wallet->amount;
        $wallet->decrement('amount', $amount);

        $this->ledger->recordCommission($provider, $amount, $balanceBefore, $bookingId);
    }

    protected function guardSufficientBalance($wallet, float $amount): void
    {
        if (($wallet->amount ?? 0) < $amount) {
            throw new \RuntimeException(
                "Insufficient wallet balance to deduct commission. Required: {$amount}, Available: {$wallet->amount}"
            );
        }
    }

    public function getProviderCommissionDetails(int $providerId): array
    {
        $earnings = CommissionEarning::where('employee_id', $providerId)
            ->with('getbooking')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCommission = $earnings->where('commission_status', '!=', 'promo')->sum('commission_amount');
        $promoCount = $earnings->where('commission_status', 'promo')->count();
        $paidCount = $earnings->where('commission_status', 'paid')->count();

        return [
            'total_commission'      => $totalCommission,
            'promo_orders_used'     => $promoCount,
            'promo_remaining'       => max(0, self::PROMO_ORDERS - $promoCount),
            'paid_commissions'      => $paidCount,
            'pending_commissions'   => $earnings->where('commission_status', 'pending')->count(),
            'earnings'              => $earnings,
        ];
    }

    public function getAdminFinancialOverview(): array
    {
        $totalCommission = CommissionEarning::sum('commission_amount');
        $paidCommission  = CommissionEarning::where('commission_status', 'paid')->sum('commission_amount');
        $pendingCommission = CommissionEarning::where('commission_status', 'pending')->sum('commission_amount');
        $promoCommission = CommissionEarning::where('commission_status', 'promo')->sum('commission_amount');

        return [
            'total_commission'    => $totalCommission,
            'paid_commission'     => $paidCommission,
            'pending_commission'  => $pendingCommission,
            'promo_commission'    => $promoCommission,
            'total_earnings_count' => CommissionEarning::count(),
            'promo_orders_count'   => CommissionEarning::where('commission_status', 'promo')->count(),
        ];
    }
}
