<?php

namespace App\Services;

use App\Models\User;
use App\Models\LegalAgreement;
use App\Models\AgreementAcceptance;
use Illuminate\Support\Facades\DB;

class AgreementService
{
    public function getActiveAgreement(string $type): ?LegalAgreement
    {
        return LegalAgreement::where('type', $type)
            ->where('is_active', true)
            ->latest('version')
            ->first();
    }

    public function hasAcceptedLatest(User $user, string $type): bool
    {
        $agreement = $this->getActiveAgreement($type);
        if (! $agreement) return false;

        return AgreementAcceptance::where('user_id', $user->id)
            ->where('legal_agreement_id', $agreement->id)
            ->exists();
    }

    public function accept(User $user, string $type, string $ipAddress, ?string $userAgent): AgreementAcceptance
    {
        $agreement = $this->getActiveAgreement($type);
        if (! $agreement) {
            throw new \RuntimeException("No active agreement found for type: {$type}");
        }

        return DB::transaction(function () use ($user, $agreement, $ipAddress, $userAgent) {
            return AgreementAcceptance::updateOrCreate(
                [
                    'user_id'            => $user->id,
                    'legal_agreement_id' => $agreement->id,
                ],
                [
                    'ip_address'   => $ipAddress,
                    'user_agent'   => $userAgent,
                    'accepted_at'  => now(),
                ]
            );
        });
    }

    public function getUserHistory(User $user): array
    {
        return AgreementAcceptance::where('user_id', $user->id)
            ->with('agreement')
            ->orderBy('accepted_at', 'desc')
            ->get()
            ->toArray();
    }
}
