<?php

namespace App\Http\Middleware;

use App\Services\AgreementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAgreementAcceptance
{
    public function __construct(
        protected AgreementService $agreementService
    ) {}

    public function handle(Request $request, Closure $next, string $type = 'provider_agreement'): Response
    {
        $user = $request->user();

        if ($user && ! $this->agreementService->hasAcceptedLatest($user, $type)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'false',
                    'message' => __('messages.please_accept_terms'),
                    'data'    => [
                        'requires_agreement' => true,
                        'agreement_type'     => $type,
                        'agreement'          => $this->agreementService->getActiveAgreement($type),
                    ],
                ], 403);
            }

            return redirect()->route('agreement.show', ['type' => $type]);
        }

        return $next($request);
    }
}
