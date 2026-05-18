<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementAcceptance extends Model
{
    use HasFactory;

    protected $table = 'agreement_acceptances';

    protected $fillable = [
        'user_id',
        'legal_agreement_id',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agreement()
    {
        return $this->belongsTo(LegalAgreement::class, 'legal_agreement_id');
    }
}
