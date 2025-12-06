<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services (Cloud Messaging, etc.)
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    'credentials_file' => env('FIREBASE_CREDENTIALS_FILE', 'firebase-credentials.json'),

    // FCM (Firebase Cloud Messaging) settings
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    // Firebase API Key
    'api_key' => env('FIREBASE_API_KEY'),

    // Web API Key
    'web_api_key' => env('FIREBASE_WEB_API_KEY'),
];
