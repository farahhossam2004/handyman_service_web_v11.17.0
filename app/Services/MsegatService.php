<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MsegatService
{
    /** MSEGAT OTP send endpoint (sendOTPCode.php). */
    protected const ENDPOINT_SEND_OTP = '/sendOTPCode.php';

    /** MSEGAT OTP verification endpoint (verifyOTPCode.php). */
    protected const ENDPOINT_VERIFY_OTP = '/verifyOTPCode.php';

    /** Whether the MSEGAT credentials have been configured. */
    private function isConfigured(): bool
    {
        return ! empty(config('services.msegat.username'))
            && ! empty(config('services.msegat.api_key'))
            && ! empty(config('services.msegat.sender'));
    }

    /** Ensure credentials are configured before talking to MSEGAT.
     *
     * @throws \RuntimeException
     */
    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('MSEGAT is not configured.');
        }
    }

    /**
     * Normalize a Saudi mobile number to the international E.164 form (966XXXXXXXXX).
     *
     * Accepts: 05XXXXXXXX, +9665XXXXXXXX, 9665XXXXXXXX, 009665XXXXXXXXX
     * Never returns a leading 00, +, or duplicated country code.
     *
     * @param  mixed  $phone
     * @return string|null  Normalized number, or null when invalid (not a Saudi mobile).
     */
    public function normalizeSaudiPhone($phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = (string) $phone;

        // Strip cosmetic characters.
        $phone = preg_replace('/[\s\-\(\)\.]/', '', trim($phone));
        if ($phone === '') {
            return null;
        }

        // Remove leading '+' then any international prefix '00'.
        $phone = ltrim($phone, '+');
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        // Remove a leading country code if already present (idempotent).
        if (str_starts_with($phone, '966')) {
            $phone = substr($phone, 3);
        }

        // Remove the local trunk prefix '0' (e.g. 05XXXXXXXX).
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Guard against "966" followed directly by the trunk zero (e.g. 9660501234567).
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        $phone = preg_replace('/\D/', '', $phone);

        // Saudi mobile numbers are 9 digits starting with '5'.
        if (preg_match('/^5[0-9]{8}$/', $phone) !== 1) {
            return null;
        }

        return '966'.$phone;
    }

    /**
     * Build the HTTP client configured with the project timeouts.
     */
    private function client()
    {
        return Http::asJson()
            ->timeout((int) config('services.msegat.timeout', 15))
            ->connectTimeout((int) config('services.msegat.connect_timeout', 5));
    }

    /**
     * Send an OTP code to the given phone number.
     *
     * @return array{success: bool, request_id: string|null, code: string|null, message: string|null}
     */
    public function sendOtp(string $phone, string $lang = 'Ar'): array
    {
        $this->assertConfigured();

        $number = $this->normalizePhoneForSend($phone);

        $url = rtrim((string) config('services.msegat.base_url'), '/').self::ENDPOINT_SEND_OTP;

        $payload = [
            'apiKey'     => config('services.msegat.api_key'),
            'userName'   => config('services.msegat.username'),
            'userSender' => config('services.msegat.sender'),
            'lang'       => $lang,
            'number'     => $number,
        ];

        // TEMP DIAGNOSTIC: sanitized request metadata. Intentional NO apiKey / NO userName.
        $meta = [
            'endpoint' => $url,
            'number'   => $number,
            'sender'   => config('services.msegat.sender'),
            'lang'     => $lang,
        ];

        try {
            $response = $this->client()->post($url, $payload);

            $httpStatus = $response->status();
            $body       = $response->body();

            // TEMP DIAGNOSTIC: never logs apiKey/userName; MSEGAT response body + status code.
            Log::channel('daily')->info('MSEGAT send-otp response', $meta + [
                'http_status' => $httpStatus,
                'body'        => $body,
                'msegat_code' => $response->json('code'),
                'success'     => $response->json('success'),
                'request_id'  => $response->json('id'),
            ]);

            if ($response->failed()) {
                return $this->failure('HTTP '.$httpStatus);
            }

            $data = $response->json();
            $success = ! empty($data['success']) && $data['success'] === true;
            $message = $data['message'] ?? $body;

            return [
                'success'    => $success,
                'request_id' => $data['id'] ?? null,
                'code'       => (string) ($data['code'] ?? ''),
                'message'    => $message,
            ];
        } catch (\Throwable $e) {
            // TEMP DIAGNOSTIC: log the real exception instead of hiding it.
            Log::channel('daily')->warning('MSEGAT send-otp exception', $meta + [
                'exception'       => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            return $this->failure('network');
        }
    }

    /**
     * Verify the OTP code sent to the given phone.
     *
     * @return array{success: bool, code: string|null, message: string|null}
     */
    public function verifyOtp(string $requestId, string $code, string $lang = 'Ar'): array
    {
        $this->assertConfigured();

        $url = rtrim((string) config('services.msegat.base_url'), '/').self::ENDPOINT_VERIFY_OTP;

        $payload = [
            'apiKey'     => config('services.msegat.api_key'),
            'userName'   => config('services.msegat.username'),
            'userSender' => config('services.msegat.sender'),
            'lang'       => $lang,
            'id'         => $requestId,
            'code'       => $code,
        ];

        // TEMP DIAGNOSTIC: sanitized metadata. Intentional NO apiKey / NO userName / NO OTP code.
        $meta = [
            'endpoint'   => $url,
            'request_id' => $requestId,
            'lang'       => $lang,
        ];

        try {
            $response = $this->client()->post($url, $payload);

            $httpStatus = $response->status();
            $body       = $response->body();

            // TEMP DIAGNOSTIC: response code is MSEGAT's result code (e.g. "1"), not the OTP.
            Log::channel('daily')->info('MSEGAT verify-otp response', $meta + [
                'http_status' => $httpStatus,
                'body'        => $body,
                'msegat_code' => $response->json('code'),
                'success'     => $response->json('success'),
            ]);

            if ($response->failed()) {
                return $this->failure('HTTP '.$httpStatus);
            }

            $data = $response->json();

            $responseCode = (string) ($data['code'] ?? '');
            $message = $data['message'] ?? $body;

            // MSEGAT returns code "1" on successful verification.
            $success = $responseCode === '1' || (bool) ($data['success'] ?? false);

            return [
                'success' => $success,
                'code'    => $responseCode,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            // TEMP DIAGNOSTIC: log the real exception instead of hiding it.
            Log::channel('daily')->warning('MSEGAT verify-otp exception', $meta + [
                'exception'       => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            return $this->failure('network');
        }
    }

    /**
     * Normalize a number for the actual MSEGAT request; ensures a valid value before sending.
     *
     * @throws \InvalidArgumentException
     */
    private function normalizePhoneForSend(string $phone): string
    {
        $normalized = $this->normalizeSaudiPhone($phone);

        if ($normalized === null) {
            throw new \InvalidArgumentException('Invalid Saudi mobile number.');
        }

        return $normalized;
    }

    /**
     * Whether critical MSEGAT settings are present (public check for gatekeeping).
     */
    public function isConfiguredPublic(): bool
    {
        return $this->isConfigured();
    }

    /**
     * Create a normalized failure payload.
     *
     * @return array{success: false, code: string, message: string}
     */
    private function failure(string $reason): array
    {
        return [
            'success' => false,
            'code'    => $reason,
            'message' => 'MSEGAT request failed.',
        ];
    }
}