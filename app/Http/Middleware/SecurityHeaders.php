<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking - Disallow framing of the application
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection in older browsers
        $response->header('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - Control how much referrer information is shared
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy) - Control browser features
        $response->header(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        );

        // Content Security Policy - Prevent XSS and injection attacks
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js https://cdn.tailwindcss.com https://cdn.jsdelivr.net/npm/tesseract.js@5.1.0/dist/tesseract.min.js; " .
               "worker-src 'self' blob:; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' data: https:; " .
               "connect-src 'self' https: blob:; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'";

        $response->header('Content-Security-Policy', $csp);

        // Strict Transport Security - Force HTTPS
        if (config('app.env') === 'production') {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Expect-CT - Certificate Transparency logging requirement
        if (config('app.env') === 'production') {
            $response->header('Expect-CT', 'max-age=86400, enforce');
        }

        // Remove server identification header
        $response->headers->remove('Server');

        // Additional headers
        $response->header('X-Permitted-Cross-Domain-Policies', 'none');

        return $response;
    }
}
