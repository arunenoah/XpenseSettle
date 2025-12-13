<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a nonce for inline scripts BEFORE rendering the view
        $nonce = bin2hex(random_bytes(16));
        $request->attributes->set('nonce', $nonce);

        // Process the request and generate the response
        $response = $next($request);

        // Skip adding security headers to file responses (they handle their own headers)
        if ($this->isFileResponse($response)) {
            return $response;
        }

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
        // Allow inline scripts and external HTTPS scripts
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' blob: https:; " .
               "worker-src 'self' blob: data:; " .
               "style-src 'self' 'unsafe-inline' https:; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' data: https:; " .
               "connect-src 'self' https: blob: data:; " .
               "frame-ancestors 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'; " .
               "upgrade-insecure-requests; " .
               "block-all-mixed-content";

        $response->header('Content-Security-Policy', $csp);

        // Strict Transport Security - Force HTTPS
        if (config('app.env') === 'production') {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Expect-CT - Certificate Transparency logging requirement
        if (config('app.env') === 'production') {
            $response->header('Expect-CT', 'max-age=86400, enforce');
        }

        // Public Key Pinning - Pin certificate keys (production only)
        if (config('app.env') === 'production') {
            $response->header('Public-Key-Pins', "max-age=2592000; pin-sha256=\"<your-cert-sha256-here>\"; pin-sha256=\"<backup-cert-sha256-here>\"; includeSubDomains");
        }

        // Remove server identification header
        $response->headers->remove('Server');

        // Additional headers for enhanced security
        $response->header('X-Permitted-Cross-Domain-Policies', 'none');

        // Cross-Origin-Embedder-Policy - require cross-origin resources to be explicitly allowed
        $response->header('Cross-Origin-Embedder-Policy', 'require-corp');

        // Cross-Origin-Opener-Policy - isolate the browsing context
        $response->header('Cross-Origin-Opener-Policy', 'same-origin');

        // Cross-Origin-Resource-Policy - control cross-origin resource sharing
        $response->header('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }

    /**
     * Check if the response is a file response (binary file or stream).
     */
    private function isFileResponse(Response $response): bool
    {
        return $response instanceof StreamedResponse || $response instanceof BinaryFileResponse;
    }
}
