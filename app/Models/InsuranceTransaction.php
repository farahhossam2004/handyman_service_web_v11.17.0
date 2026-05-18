<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceTransaction extends Model
{
    use HasFactory;

    protected $table = 'insurance_transactions';

    protected $fillable = [
        'user_id',
        'related_type',
        'related_id',
        'amount',
        'type',
        'direction',
        'balance_before',
        'balance_after',
        'reason',
        'actioned_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function actionedBy()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
