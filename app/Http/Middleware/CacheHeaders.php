<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't modify headers for file downloads/streams (BinaryFileResponse, StreamedResponse)
        // These responses handle their own headers and can't use the header() method safely
        if ($this->isFileResponse($response)) {
            return $response;
        }

        // Cache static assets for a long time (1 year)
        if ($this->isStaticAsset($request->getPathInfo())) {
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
        // Cache API responses based on method
        elseif (strpos($request->getPathInfo(), '/api/') === 0) {
            if ($request->isMethod('GET')) {
                // Cache GET requests for 5 minutes
                $response->header('Cache-Control', 'private, max-age=300');
            } else {
                // Don't cache POST, PUT, DELETE
                $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
            }
        }
        // Default for HTML pages - minimal caching
        else {
            $response->header('Cache-Control', 'private, must-revalidate, max-age=0');
            $response->header('Pragma', 'no-cache');
        }

        // Add ETag for client-side caching validation (only for responses with content)
        try {
            if ($response->getContent()) {
                $etag = '"' . hash('sha256', $response->getContent()) . '"';
                $response->header('ETag', $etag);

                // Return 304 Not Modified if ETag matches
                if ($request->header('If-None-Match') === $etag) {
                    return response('', 304);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors when getting content (some responses don't support it)
        }

        return $response;
    }

    /**
     * Check if the response is a file response (binary file or stream).
     * These responses shouldn't have cache headers modified.
     */
    private function isFileResponse(Response $response): bool
    {
        $responseClass = get_class($response);

        // Check if it's a BinaryFileResponse or StreamedResponse
        return $responseClass === 'Symfony\Component\HttpFoundation\BinaryFileResponse' ||
               $responseClass === 'Symfony\Component\HttpFoundation\StreamedResponse' ||
               strpos($responseClass, 'BinaryFileResponse') !== false ||
               strpos($responseClass, 'StreamedResponse') !== false;
    }

    /**
     * Check if the request is for a static asset.
     */
    private function isStaticAsset(string $path): bool
    {
        $staticExtensions = [
            '.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.svg',
            '.woff', '.woff2', '.ttf', '.eot', '.ico', '.webp', '.mp4',
            '.webm', '.pdf', '.zip', '.json'
        ];

        foreach ($staticExtensions as $ext) {
            if (str_ends_with(strtolower($path), $ext)) {
                return true;
            }
        }

        return false;
    }
}
