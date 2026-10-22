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
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
        'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
    ],

    // Instagram DMs and Facebook Messenger. Per-account tokens/secrets are stored (encrypted) in channel_accounts;
    // these are optional app-wide fallbacks for the webhook handshake and signature.
    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_VERIFY_TOKEN'),
        'app_id' => env('META_APP_ID'),
        // Optional: when set, the webhook only answers at /webhooks/meta/{token}.
        'webhook_token' => env('META_WEBHOOK_TOKEN'),
        'graph_version' => env('META_GRAPH_VERSION', env('WHATSAPP_GRAPH_VERSION', 'v21.0')),
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
