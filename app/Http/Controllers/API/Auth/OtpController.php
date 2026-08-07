<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Services\MsegatService;
use App\Services\AgreementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    public function __construct(
        protected MsegatService $msegat,
        protected AgreementService $agreements,
    ) {}

    /**
     * POST /api/auth/send-otp
     *
     * Validates a Saudi phone number, enforces rate/cooldown limits, invalidates any
     * previous active OTP for the phone, calls MSEGAT, and stores only the request id.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'          => ['required', 'string'],
            'type'           => ['required', 'string', 'in:customer,user,provider,handyman'],
            'name'           => ['nullable', 'string', 'max:255'],
            'password'       => ['nullable', 'string'], // validated below, only for registration
            'terms_accepted' => ['nullable'],
            'referral_code'  => ['nullable', 'string'],
        ]);

        $type = $data['type'];

        // Customer registration requires the pre-registration payload up front so the
        // account can be created after verification without a second round-trip.
        if ($type === 'customer') {
            $data = array_merge($data, $request->validate([
                'name'           => ['required', 'string', 'max:255'],
                'password'       => ['required', 'string', 'min:8', 'max:12', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,12}$/'],
                'terms_accepted' => ['required', 'accepted'],
            ]));
        }

        $normalized = $this->msegat->normalizeSaudiPhone($data['phone']);
        if ($normalized === null) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.invalid_mobile_number'),
            ], 422);
        }

        $phone = $normalized;
        $type  = $data['type'];
        $purpose = 'registration';

        // If the phone is already a registered customer, do not send another registration OTP.
        $existing = \App\Models\User::withTrashed()->where('contact_number', $phone)->first();
        if ($existing && $existing->deleted_at === null) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.phone_already_registered'),
            ], 409);
        }

        // Application-level cooldown: 1 request per <cooldown> seconds per phone.
        $recent = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if ($recent && $recent->isInCooldown()) {
            $seconds = max(1, $recent->cooldown_until->diffInSeconds(now()));
            return response()->json([
                'status'  => false,
                'message' => __('messages.msegat_resend_cooldown', ['seconds' => $seconds]),
                'retry_after' => $seconds,
            ], 429);
        }

        try {
            $result = $this->msegat->sendOtp($phone, 'Ar');
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.invalid_mobile_number'),
            ], 422);
        } catch (\RuntimeException $e) {
            Log::warning('MSEGAT not configured during send-otp request.');
            return response()->json([
                'status'  => false,
                'message' => __('messages.sms_service_unavailable'),
            ], 503);
        }

        if (! $result['success'] || empty($result['request_id'])) {
            $message = $this->mapMsegatMessage($result['code'] ?? '', 'send');
            return response()->json([
                'status'  => false,
                'message' => $message,
            ], 422);
        }

        // Invalidate any previously issued, not-yet-used OTPs for this phone+purpose
        // so only the newest verification is valid (prevents reuse/brute-force).
        OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->update(['msegat_request_id' => null]);

        $expiresInMinutes = (int) config('services.msegat.otp_expires_in_minutes', 8);
        $cooldownSeconds = (int) config('services.msegat.otp_resend_cooldown', 60);

        $record = OtpVerification::create([
            'phone'             => $phone,
            'purpose'           => $purpose,
            'account_type'      => $type,
            'msegat_request_id' => $result['request_id'],
            'metadata'          => $this->registrationMetadata($request, $type),
            'expires_at'        => now()->addMinutes($expiresInMinutes),
            'cooldown_until'    => now()->addSeconds($cooldownSeconds),
        ]);

        return response()->json([
            'status'  => true,
            'message' => __('messages.otp_sent'),
            'data'    => [
                'verification_id' => $record->id,
            ],
        ]);
    }

    /**
     * POST /api/auth/verify-otp
     *
     * Verifies the submitted code with MSEGAT, guards against brute force / expiry /
     * reuse, and only completes the customer registration after successful verification.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'verification_id' => ['required', 'integer'],
            'code'            => ['required', 'string'],
        ]);

        $record = OtpVerification::find($data['verification_id']);
        if (! $record) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.otp_not_found'),
            ], 404);
        }

        if ($record->isVerified()) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.otp_already_used'),
            ], 422);
        }

        if ($record->isExpired()) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.otp_expired'),
            ], 422);
        }

        if ($record->maxAttemptsReached()) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.otp_max_attempts'),
            ], 429);
        }

        // The request id is cleared when a newer OTP is issued for the same phone,
        // which invalidates this record (prevents reuse/brute-force across resends).
        if (empty($record->msegat_request_id)) {
            return response()->json([
                'status'  => false,
                'message' => __('messages.otp_invalid_or_expired'),
            ], 422);
        }

        $record->increment('attempts');
        $record->forceFill(['last_attempt_at' => now()])->save();

        $lang = 'Ar';
        $result = $this->msegat->verifyOtp((string) $record->msegat_request_id, $data['code'], $lang);

        if (! $result['success']) {
            $record->save();
            $message = $this->mapMsegatMessage($result['code'] ?? '', 'verify');
            return response()->json([
                'status'  => false,
                'message' => $message,
            ], 422);
        }

        $record->forceFill(['verified_at' => now(), 'msegat_request_id' => null])->save();

        // Registration is only completed AFTER successful OTP verification.
        if ($record->purpose === 'registration' && $record->account_type === 'customer') {
            $account = $this->createCustomerFromVerification($record);
            if ($account === null) {
                return response()->json([
                    'status'  => false,
                    'message' => __('messages.registration_failed'),
                ], 422);
            }

            return response()->json([
                'status'  => true,
                'message' => __('messages.phoone_verification_success'),
                'data'    => $this->customerPayload($account),
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => __('messages.phoone_verification_success'),
            'data'    => [],
        ]);
    }

    /**
     * Build encrypted-without-the-OTP metadata for registration; the password is hashed.
     */
    private function registrationMetadata(Request $request, string $type): ?array
    {
        if ($type !== 'customer') {
            return null;
        }

        $name = $request->input('name');
        $password = $request->input('password');

        if (empty($name) || empty($password)) {
            return null;
        }

        return [
            'name'           => $name,
            'password_hash'  => Hash::make($password),
            'terms_accepted' => (bool) $request->boolean('terms_accepted'),
            'referral_code'  => $request->input('referral_code'),
        ];
    }

    /**
     * Create the customer account from verified registration metadata.
     * Returns null when the pre-registration payload is missing/incomplete.
     */
    private function createCustomerFromVerification(OtpVerification $record)
    {
        $meta = $record->metadata;

        if (empty($meta['name']) || empty($meta['password_hash'])) {
            return null;
        }

        $username = $this->generateUniqueUsername();
        $email = $this->generateUniqueEmail($username);

        // Prevent duplicate customer for the same verified phone.
        if (\App\Models\User::withTrashed()->where('contact_number', $record->phone)->exists()) {
            return null;
        }

        $input = [
            'username'       => $username,
            'email'          => $email,
            'first_name'     => $meta['name'],
            'last_name'      => '',
            'display_name'   => $meta['name'],
            'phone'          => $record->phone,
            'contact_number' => $record->phone,
            'password'       => $meta['password_hash'],
            'user_type'      => 'user',
            'terms_accepted' => ! empty($meta['terms_accepted']),
            'referred_by'    => null,
        ];

        if (! empty($meta['referral_code'])) {
            $referrer = \App\Models\User::where('referral_code', $meta['referral_code'])->first();
            if ($referrer) {
                $input['referred_by'] = $referrer->id;
            }
        }

        try {
            $user = \App\Models\User::create($input);
            $user->assignRole('user');
            \App\Models\Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['title' => $user->display_name, 'amount' => 0]
            );

            // Recording terms acceptance must never block account creation.
            if (! empty($meta['terms_accepted'])) {
                try {
                    $this->agreements->accept($user, 'customer_agreement', request()->ip(), request()->userAgent());
                } catch (\Throwable $e) {
                    Log::warning('[OTP] Terms acceptance not recorded: '.$e->getMessage(), ['user_id' => $user->id]);
                }
            }

            return $user;
        } catch (\Throwable $e) {
            Log::error('[OTP] Customer registration failed: '.$e->getMessage(), ['phone' => $record->phone]);
            return null;
        }
    }

    private function customerPayload($user): array
    {
        $data = $user->toArray();
        $data['api_token'] = $user->createToken('auth_token')->plainTextToken;
        $data['user_role'] = 'user';
        $data['profile_image'] = null;

        return $data;
    }

    private function generateUniqueUsername(): string
    {
        do {
            $username = 'customer_'.mt_rand(10000, 99999);
        } while (\App\Models\User::where('username', $username)->exists());

        return $username;
    }

    private function generateUniqueEmail(string $username): string
    {
        $email = $username.'@app.local';
        while (\App\Models\User::where('email', $email)->exists()) {
            $username = $this->generateUniqueUsername();
            $email = $username.'@app.local';
        }

        return $email;
    }

    /**
     * Map MSEGAT codes to user-friendly Arabic messages.
     */
    private function mapMsegatMessage(string $code, string $operation): string
    {
        if ($operation === 'verify') {
            return match ($code) {
                '400'           => __('messages.otp_expired'),
                '404'           => __('messages.otp_not_found'),
                '2', '3'        => __('messages.otp_wrong_code'),
                default         => __('messages.otp_wrong_code'),
            };
        }

        return match ($code) {
            '1010', 'M0001' => __('messages.msegat_incomplete'),
            '1020', 'M0002' => __('messages.msegat_service_error'),
            '1060'          => __('messages.msegat_insufficient_balance'),
            '1110'          => __('messages.msegat_invalid_sender'),
            '1120'          => __('messages.msegat_invalid_number'),
            default         => __('messages.otp_send_failed'),
        };
    }
}