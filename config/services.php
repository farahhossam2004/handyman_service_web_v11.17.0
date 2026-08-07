<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'locale' => env('GOOGLE_MAPS_LOCALE', 'us'),
    ],

    'do_spaces' => [
        'media_disk' => env('MEDIA_DISK', 'public'),
        'path' => env('DO_SPACES_PATH', ''),
    ],

    'msegat' => [
        'username' => env('MSEGAT_USERNAME'),
        'api_key'  => env('MSEGAT_API_KEY'),
        'sender'   => env('MSEGAT_SENDER'),
        'base_url' => env('MSEGAT_BASE_URL', 'https://www.msegat.com/gw'),
        'timeout'  => 15,
        'connect_timeout' => 5,
        // OTP lifecycle limits (seconds / minutes / attempts)
        'otp_expires_in_minutes' => env('MSEGAT_OTP_EXPIRES_MINUTES', 8),
        'otp_max_attempts'       => (int) env('MSEGAT_OTP_MAX_ATTEMPTS', 5),
        'otp_resend_cooldown'    => (int) env('MSEGAT_OTP_RESEND_COOLDOWN', 60),
        'otp_send_rate_limit'    => (int) env('MSEGAT_OTP_SEND_RATE_LIMIT', 1),
    ],


    // 'onesignal' => [
    //     'app_id' => env('ONESIGNAL_API_KEY'),
    //     'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    //     'ONESIGNAL_APP_ID_PROVIDER' => env('ONESIGNAL_APP_ID_PROVIDER'),
    //     'ONESIGNAL_REST_API_KEY_PROVIDER' => env('ONESIGNAL_REST_API_KEY_PROVIDER'),
    // ],

];
