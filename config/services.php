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

    'yo_payments' => [
        'username' => env('YO_PAYMENTS_USERNAME', env('API_USERNAME_YOPAYMENTS')),
        'password' => env('YO_PAYMENTS_PASSWORD', env('API_PASSWORD_YOPAYMENTS')),
        'sandbox' => env('YO_PAYMENTS_SANDBOX', false),
        // API v3.44 section 3.7 — production endpoints
        'production_url' => env('YO_PAYMENTS_PRODUCTION_URL', env('API_URL_YOPAYMENTS', 'https://paymentsapi1.yo.co.ug/ybs/task.php')),
        'production_url_fallback' => env('YO_PAYMENTS_PRODUCTION_URL_FALLBACK', 'https://paymentsapi2.yo.co.ug/ybs/task.php'),
        // API v3.44 section 24.3 — sandbox endpoint
        'sandbox_url' => env('YO_PAYMENTS_SANDBOX_URL', 'https://sandbox.yo.co.ug/services/yopaymentsdev/task.php'),
        'production_public_key' => env('YO_PAYMENTS_PRODUCTION_PUBLIC_KEY', storage_path('certificates/Yo_Uganda_Public_Certificate.crt')),
        'sandbox_public_key' => env('YO_PAYMENTS_SANDBOX_PUBLIC_KEY', storage_path('certificates/Yo_Uganda_Public_Sandbox_Certificate.crt')),
        // Also configure a default IPN URL in your Yo! Business account portal (section 6.3.1)
        'skip_signature_verification' => env('YO_PAYMENTS_SKIP_SIGNATURE_VERIFICATION', false),
        // Respond to IPN with narrative= to trigger payer SMS (section 6.3.2)
        'ipn_sms_response' => env('YO_PAYMENTS_IPN_SMS_RESPONSE', true),
    ],

];
