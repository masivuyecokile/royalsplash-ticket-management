<?php

return [
    'app_name' => $_ENV['APP_NAME'] ?? 'Royal Splash',
    'app_url' => $_ENV['APP_URL'] ?? 'http://localhost:8088',
    'app_env' => $_ENV['APP_ENV'] ?? 'local',

    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'royal_splash',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ],

    'payfast' => [
        'mode' => $_ENV['PAYFAST_MODE'] ?? 'sandbox',

        'sandbox' => [
            'merchant_id' => $_ENV['PAYFAST_SANDBOX_MERCHANT_ID'] ?? '',
            'merchant_key' => $_ENV['PAYFAST_SANDBOX_MERCHANT_KEY'] ?? '',
            'passphrase' => $_ENV['PAYFAST_SANDBOX_PASSPHRASE'] ?? '',
            'process_url' => $_ENV['PAYFAST_SANDBOX_PROCESS_URL'] ?? 'https://sandbox.payfast.co.za/eng/process',
            'validate_url' => $_ENV['PAYFAST_SANDBOX_VALIDATE_URL'] ?? 'https://sandbox.payfast.co.za/eng/query/validate',
            ],

        'live' => [
            'merchant_id' => $_ENV['PAYFAST_LIVE_MERCHANT_ID'] ?? '',
            'merchant_key' => $_ENV['PAYFAST_LIVE_MERCHANT_KEY'] ?? '',
            'passphrase' => $_ENV['PAYFAST_LIVE_PASSPHRASE'] ?? '',
            'process_url' => $_ENV['PAYFAST_LIVE_PROCESS_URL'] ?? 'https://www.payfast.co.za/eng/process',
            'validate_url' => $_ENV['PAYFAST_LIVE_VALIDATE_URL'] ?? 'https://www.payfast.co.za/eng/query/validate',
            ],
        ],

    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',

        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Royal Splash',
        'reply_to' => $_ENV['MAIL_REPLY_TO'] ?? '',
        ],

    'google_wallet' => [
    'enabled' => filter_var($_ENV['GOOGLE_WALLET_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'issuer_id' => $_ENV['GOOGLE_WALLET_ISSUER_ID'] ?? '',
    'issuer_name' => $_ENV['GOOGLE_WALLET_ISSUER_NAME'] ?? 'Royal Splash',
    'service_account_json' => $_ENV['GOOGLE_WALLET_SERVICE_ACCOUNT_JSON'] ?? '',
    'origin' => $_ENV['GOOGLE_WALLET_ORIGIN'] ?? '',
     'event_class_id' => $_ENV['GOOGLE_WALLET_EVENT_CLASS_ID'] ?? '',
    'hero_image_url' => $_ENV['GOOGLE_WALLET_HERO_IMAGE_URL'] ?? '',
],
    ];
