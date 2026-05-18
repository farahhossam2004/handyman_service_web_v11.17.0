<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $table = 'wallets';
    protected $fillable = [
        'user_id', 'title', 'amount', 'status',
        'available_balance', 'escrow_balance', 'insurance_balance', 'frozen_balance',
    ];
    protected $casts = [
        'user_id'           => 'integer',
        'amount'            => 'double',
        'available_balance' => 'double',
        'escrow_balance'    => 'double',
        'insurance_balance' => 'double',
        'frozen_balance'    => 'double',
        'status'            => 'integer',
    ];

    public function providers()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeList($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }

    public function syncBalances(): void
    {
        $escrowHeld = EscrowTransaction::where(function ($q) {
            $q->where('customer_id', $this->user_id)
              ->orWhere('provider_id', $this->user_id);
        })->whereIn('status', ['held', 'frozen_under_investigation'])
        ->sum('held_amount');

        $frozen = EscrowTransaction::where(function ($q) {
            $q->where('customer_id', $this->user_id)
              ->orWhere('provider_id', $this->user_id);
        })->where('status', 'frozen_under_investigation')
        ->sum('held_amount');

        $user = User::find($this->user_id);

        $this->update([
            'available_balance' => $this->amount ?? 0,
            'escrow_balance'    => $escrowHeld,
            'insurance_balance' => $user ? $user->insurance_balance : 0,
            'frozen_balance'    => $frozen,
        ]);

        \App\Services\DashboardService::clearCache();
    }
}
