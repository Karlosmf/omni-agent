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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // WhatsApp Cloud API (Meta). Secrets live in .env, never in the database.
    'whatsapp' => [
        // "cloud" = Meta's Cloud API with the agency's own app; "zernio" = through Zernio (allows coexistence).
        'provider' => env('WHATSAPP_PROVIDER', 'cloud'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v26.0'),
        'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
    ],

    // WhatsApp through Zernio (a Meta Tech Provider): needed to keep the number in the Business app (coexistence).
    'zernio' => [
        'api_key' => env('ZERNIO_API_KEY'),
        'whatsapp_account_id' => env('ZERNIO_WHATSAPP_ACCOUNT_ID'),
        // The secret you set when registering the webhook in Zernio; it signs every event.
        'webhook_secret' => env('ZERNIO_WEBHOOK_SECRET'),
        'base_url' => env('ZERNIO_BASE_URL', 'https://zernio.com/api/v1'),
        // Test mode: comma-separated numbers. When set, events from any other number are ignored
        // (useful while testing on a personal number). Leave empty in production.
        'test_phones' => env('ZERNIO_TEST_PHONES'),
    ],

    // Instagram DMs and Facebook Messenger. Per-account tokens/secrets are stored (encrypted) in channel_accounts;
    // these are optional app-wide fallbacks for the webhook handshake and signature.
    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'app_id' => env('META_APP_ID'),
        // Optional: when set, the webhook only answers at /webhooks/meta/{token}.
        'webhook_token' => env('META_WEBHOOK_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', env('WHATSAPP_GRAPH_VERSION', 'v26.0')),
        'instagram_graph_url' => env('META_INSTAGRAM_GRAPH_URL', 'https://graph.instagram.com'),
    ],

    // Optional bearer token for POST /api/leads. Empty keeps the endpoint open (web forms, integrations).
    'leads_api' => [
        'token' => env('LEADS_API_TOKEN'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

];
