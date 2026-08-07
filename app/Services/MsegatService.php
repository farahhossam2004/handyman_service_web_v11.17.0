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

        $payload = [
            'apiKey'     => config('services.msegat.api_key'),
            'userName'   => config('services.msegat.username'),
            'userSender' => config('services.msegat.sender'),
            'lang'       => $lang,
            'number'     => $number,
        ];

        $url = rtrim((string) config('services.msegat.base_url'), '/').self::ENDPOINT_SEND_OTP;

        try {
            $response = $this->client()->post($url, $payload);

            if ($response->failed()) {
                $this->log('send', $number, ['http_status' => $response->status(), 'body' => $response->body()]);
                return $this->failure('HTTP '.$response->status());
            }

            $data = $response->json();
            $success = ! empty($data['success']) && $data['success'] === true;
            $message = $data['message'] ?? $response->body();

            $this->log('send', $number, [
                'raw' => $this->scrub(is_array($data) ? $data : ['message' => $message]),
            ]);

            return [
                'success'  => $success,
                'request_id' => $data['id'] ?? null,
                'code'     => (string) ($data['code'] ?? ''),
                'message'  => $message,
            ];
        } catch (\Throwable $e) {
            $this->log('send', $number, ['exception' => $e->getMessage()]);
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

        $payload = [
            'apiKey'     => config('services.msegat.api_key'),
            'userName'   => config('services.msegat.username'),
            'userSender' => config('services.msegat.sender'),
            'lang'       => $lang,
            'id'         => $requestId,
            'code'       => $code,
        ];

        $url = rtrim((string) config('services.msegat.base_url'), '/').self::ENDPOINT_VERIFY_OTP;

        try {
            $response = $this->client()->post($url, $payload);

            if ($response->failed()) {
                $this->log('verify', $requestId, ['http_status' => $response->status(), 'body' => $response->body()]);
                return $this->failure('HTTP '.$response->status());
            }

            $data = $response->json();

            $responseCode = (string) ($data['code'] ?? '');
            $message = $data['message'] ?? $response->body();

            $this->log('verify', $requestId, [
                'raw' => $this->scrub(is_array($data) ? $data : ['message' => $message]),
            ]);

            // MSEGAT returns code "1" on successful verification.
            $success = $responseCode === '1' || (bool) ($data['success'] ?? false);

            return [
                'success' => $success,
                'code'    => $responseCode,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            $this->log('verify', $requestId, ['exception' => $e->getMessage()]);
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

    /**
     * Log technical details server-side WITHOUT exposing API credentials.
     */
    private function log(string $op, ?string $number, array $context = []): void
    {
        if (isset($context['raw'])) {
            $context['raw'] = $this->scrub((array) $context['raw']);
        }

        Log::channel('daily')->info('[MSEGAT] '.$op, [
            'number'  => $number,
            'context' => $context,
        ]);
    }

    /**
     * Remove credential fields from any structure before logging.
     */
    private function scrub(?array $arr): ?array
    {
        if ($arr === null) {
            return null;
        }

        unset($arr['apiKey'], $arr['userName'], $arr['userSender']);

        return $arr;
    }
}