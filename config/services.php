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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'vital' => [
        // ─── Production ───
        'prod_api_url' => env('VITAL_PROD_API_URL', ''),
        'prod_product_id' => env('VITAL_PROD_API_PRODUCT_ID', ''),
        'prod_remote_key' => env('VITAL_PROD_API_REMOTE_KEY', 'pm_super_secret_api_key'),
        'prod_verify_ssl' => env('VITAL_PROD_API_VERIFY_SSL', true),

        // ─── Staging ───
        'staging_api_url' => env('VITAL_STAGING_API_URL', ''),
        'staging_product_id' => env('VITAL_STAGING_API_PRODUCT_ID', ''),
        'staging_remote_key' => env('VITAL_STAGING_API_REMOTE_KEY', 'pm_super_secret_api_key'),
        'staging_verify_ssl' => env('VITAL_STAGING_API_VERIFY_SSL', false),
    ],

];
