<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services (Cloud Messaging, etc.)
    | All sensitive data is stored as environment variables (no files)
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    // Service Account Credentials (built from environment variables)
    'credentials' => [
        'type' => 'service_account',
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => env('FIREBASE_CLIENT_ID'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
    ],

    // FCM (Firebase Cloud Messaging) settings
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    // Firebase API Keys
    'api_key' => env('FIREBASE_API_KEY'),
    'web_api_key' => env('FIREBASE_WEB_API_KEY'),
];
