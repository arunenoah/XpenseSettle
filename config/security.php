<?php

/**
 * Security Configuration for ExpenseSettle
 *
 * This file contains all security-related configurations for the application.
 * Ensure these are properly configured for production use.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | These headers help protect against common web vulnerabilities.
    |
    */

    'headers' => [
        // X-Frame-Options: Prevents clickjacking attacks
        // DENY - Don't allow any framing
        // SAMEORIGIN - Allow only same-origin framing
        // ALLOW-FROM uri - Allow specific origin (deprecated)
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'DENY'),

        // X-Content-Type-Options: Prevent MIME type sniffing
        'content_type_options' => 'nosniff',

        // X-XSS-Protection: Enable XSS protection in older browsers
        'xss_protection' => '1; mode=block',

        // Referrer-Policy: Control referrer information
        // strict-origin-when-cross-origin - Recommended
        // no-referrer - Maximum privacy
        // same-origin - Only same-origin referrer
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

        // Permissions-Policy: Control browser feature access
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        ),

        // Strict-Transport-Security: Force HTTPS
        // max-age: Time to remember setting (31536000 = 1 year)
        // includeSubDomains: Apply to all subdomains
        // preload: Include in browser's HSTS preload list
        'hsts' => [
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],

        // Expect-CT: Require Certificate Transparency
        'expect_ct' => [
            'max_age' => env('SECURITY_EXPECT_CT_MAX_AGE', 86400),
            'enforce' => env('SECURITY_EXPECT_CT_ENFORCE', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP) Configuration
    |--------------------------------------------------------------------------
    |
    | CSP is a powerful security standard that helps prevent XSS and injection attacks.
    |
    */

    'csp' => [
        // Enforce mode or report-only mode
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),

        // Report-URI for CSP violations
        'report_uri' => env('SECURITY_CSP_REPORT_URI', null),

        // Directives
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                'https://cdn.jsdelivr.net',
                'https://cdn.tailwindcss.com',
            ],
            'style-src' => [
                "'self'",
                "'unsafe-inline'",
                'https://cdn.tailwindcss.com',
            ],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:', 'https:'],
            'connect-src' => ["'self'", 'https:'],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Control caching behavior for different types of content.
    |
    */

    'cache' => [
        // Static assets cache duration (in seconds)
        'static_assets_max_age' => env('SECURITY_STATIC_CACHE_MAX_AGE', 31536000), // 1 year

        // API response cache duration (in seconds)
        'api_get_max_age' => env('SECURITY_API_CACHE_MAX_AGE', 300), // 5 minutes

        // HTML pages cache duration (in seconds)
        'html_max_age' => env('SECURITY_HTML_CACHE_MAX_AGE', 0), // No cache

        // Use ETags for cache validation
        'use_etags' => env('SECURITY_USE_ETAGS', true),

        // Enable compression
        'enable_compression' => env('SECURITY_ENABLE_COMPRESSION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security Configuration
    |--------------------------------------------------------------------------
    |
    | Secure session handling and cookie settings.
    |
    */

    'session' => [
        // Session cookie security
        'secure' => env('SESSION_SECURE_COOKIES', env('APP_ENV') === 'production'),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'lax'), // 'strict', 'lax', 'none'

        // Session timeout (in minutes)
        'timeout' => env('SESSION_TIMEOUT', 120),

        // Regenerate session ID on authentication
        'regenerate_on_auth' => env('SESSION_REGENERATE_ON_AUTH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CSRF token handling and exclusions.
    |
    */

    'csrf' => [
        // Paths excluded from CSRF verification
        'except' => [
            // Add paths that don't need CSRF protection (webhooks, etc.)
        ],

        // Header name for CSRF token
        'header' => env('CSRF_HEADER_NAME', 'X-CSRF-TOKEN'),

        // Include CSRF token in AJAX requests
        'include_in_ajax' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Prevent brute force and DDoS attacks.
    |
    */

    'rate_limiting' => [
        // Login attempt limiting
        'login_attempts' => env('SECURITY_LOGIN_ATTEMPTS', 5),
        'login_timeout_minutes' => env('SECURITY_LOGIN_TIMEOUT', 15),

        // API rate limiting
        'api_requests_per_minute' => env('SECURITY_API_RATE_LIMIT', 60),
        'api_requests_per_hour' => env('SECURITY_API_RATE_LIMIT_HOUR', 1000),

        // General rate limiting
        'general_requests_per_minute' => env('SECURITY_GENERAL_RATE_LIMIT', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure password requirements and hashing.
    |
    */

    'password' => [
        // Minimum password length
        'min_length' => env('SECURITY_PASSWORD_MIN_LENGTH', 12),

        // Require uppercase letters
        'require_uppercase' => true,

        // Require lowercase letters
        'require_lowercase' => true,

        // Require numbers
        'require_numbers' => true,

        // Require special characters
        'require_special_chars' => true,

        // Password expiry (in days, 0 = never expires)
        'expiry_days' => env('SECURITY_PASSWORD_EXPIRY', 0),

        // Number of previous passwords to remember (prevent reuse)
        'remember_count' => env('SECURITY_PASSWORD_REMEMBER_COUNT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS/SSL Configuration
    |--------------------------------------------------------------------------
    |
    | Force HTTPS and SSL certificate settings.
    |
    */

    'https' => [
        // Force HTTPS in production
        'enabled' => env('SECURITY_HTTPS_ENABLED', env('APP_ENV') === 'production'),

        // SSL certificate path (if using custom certificates)
        'cert_path' => env('SECURITY_SSL_CERT_PATH', null),
        'key_path' => env('SECURITY_SSL_KEY_PATH', null),

        // SSL verification
        'verify_peer' => env('SECURITY_SSL_VERIFY_PEER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Track security-related events.
    |
    */

    'logging' => [
        // Log authentication attempts
        'log_auth_attempts' => env('SECURITY_LOG_AUTH', true),

        // Log failed CSRF tokens
        'log_csrf_failures' => env('SECURITY_LOG_CSRF', true),

        // Log rate limit violations
        'log_rate_limit' => env('SECURITY_LOG_RATE_LIMIT', true),

        // Log security headers
        'log_headers' => env('SECURITY_LOG_HEADERS', false),

        // Log file path
        'log_path' => storage_path('logs/security.log'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Configure secure file upload handling.
    |
    */

    'uploads' => [
        // Maximum file size in bytes
        'max_size' => env('SECURITY_MAX_UPLOAD_SIZE', 5242880), // 5MB

        // Allowed MIME types
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ],

        // Allowed file extensions
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],

        // Scan uploaded files (antivirus, etc.)
        'scan_enabled' => env('SECURITY_FILE_SCAN', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure data encryption and protection.
    |
    */

    'data_protection' => [
        // Encryption cipher
        'cipher' => env('APP_CIPHER', 'AES-256-CBC'),

        // Encrypt sensitive database columns
        'encrypt_sensitive_data' => true,

        // Sensitive data fields (automatically encrypted)
        'sensitive_fields' => [
            // 'bank_account',
            // 'credit_card',
            // 'ssn',
        ],
    ],
];
