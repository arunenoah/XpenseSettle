<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the settings required for
    | integrating Google Cloud Vision API for OCR functionality.
    |
    */

    'vision' => [
        'enabled' => env('GOOGLE_CLOUD_VISION_ENABLED', false),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),

        // Two methods to provide credentials:
        // Method 1 (Recommended): JSON credentials directly in .env
        'credentials' => env('GOOGLE_CLOUD_CREDENTIALS'),

        // Method 2 (Alternative): Path to service account key file
        'key_file' => env('GOOGLE_CLOUD_KEY_FILE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Settings
    |--------------------------------------------------------------------------
    */
    'ocr' => [
        // Maximum file size for OCR processing (in bytes) - 20MB
        'max_file_size' => env('OCR_MAX_FILE_SIZE', 20 * 1024 * 1024),

        // Supported image formats for OCR
        'supported_formats' => ['jpeg', 'png', 'gif', 'bmp', 'webp'],

        // Language hint for better OCR accuracy
        'language_hints' => env('OCR_LANGUAGE_HINTS', ['en']),

        // Confidence threshold for text detection (0-1)
        'min_confidence' => env('OCR_MIN_CONFIDENCE', 0.7),

        // Enable caching of OCR results
        'cache_results' => env('OCR_CACHE_RESULTS', true),
        'cache_ttl' => env('OCR_CACHE_TTL', 3600), // 1 hour in seconds

        // Maximum number of concurrent OCR requests
        'max_concurrent_requests' => env('OCR_MAX_CONCURRENT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Access Control
    |--------------------------------------------------------------------------
    */
    'plans' => [
        'free' => [
            'monthly_ocr_scans' => 5,
            'daily_ocr_scans' => 2,
        ],
        'trip_pass' => [
            'monthly_ocr_scans' => 100,
            'daily_ocr_scans' => 20,
        ],
        'lifetime' => [
            'monthly_ocr_scans' => PHP_INT_MAX,
            'daily_ocr_scans' => PHP_INT_MAX,
        ],
    ],
];
