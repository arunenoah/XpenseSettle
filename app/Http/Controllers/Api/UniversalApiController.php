<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiDispatcherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Throwable;

/**
 * Universal API Controller
 *
 * Generic handler for all API requests
 * Routes method parameter to appropriate controller action
 * Handles errors and returns consistent JSON responses
 *
 * Usage:
 * GET  /api/v1/execute?method=groups.list
 * POST /api/v1/execute?method=groups.create&group_id=1
 * PUT  /api/v1/execute?method=groups.update&group_id=1
 * DELETE /api/v1/execute?method=groups.delete&group_id=1
 */
class UniversalApiController extends Controller
{
    private ApiDispatcherService $dispatcher;

    public function __construct(ApiDispatcherService $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Execute API method via dispatcher
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request): JsonResponse
    {
        try {
            // Get method name from query parameter
            $method = $request->query('method') ?? $request->input('method');

            if (!$method) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameter: method',
                    'example' => 'GET /api/v1/execute?method=groups.list',
                ], 400);
            }

            // Extract route parameters from query/input
            $params = $this->extractRouteParams($request, $method);

            // Dispatch method
            $result = $this->dispatcher->dispatch($method, $request, $params);

            return response()->json([
                'success' => true,
                'message' => 'API call successful',
                'data' => $result,
            ], 200);

        } catch (Exception $e) {
            return $this->handleError($e);
        } catch (Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * List all available API methods
     *
     * @return JsonResponse
     */
    public function methods(): JsonResponse
    {
        try {
            $methods = $this->dispatcher->getAvailableMethods();

            return response()->json([
                'success' => true,
                'message' => 'Available API methods',
                'count' => count($methods),
                'data' => $methods,
            ], 200);

        } catch (Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Extract route parameters from request
     *
     * Expected format in query/body:
     * - group_id, expense_id, payment_id, etc.
     *
     * @param Request $request
     * @param string $method API method name
     * @return array Route parameters
     */
    private function extractRouteParams(Request $request, string $method): array
    {
        $params = [];

        // Common parameter mappings
        $paramMappings = [
            'group' => ['group_id', 'group'],
            'expense' => ['expense_id', 'expense'],
            'payment' => ['payment_id', 'payment'],
            'user' => ['user_id', 'user'],
            'member' => ['member_id', 'member'],
            'notification' => ['notification_id', 'notification'],
        ];

        // Extract from request
        foreach ($paramMappings as $paramName => $possibleKeys) {
            foreach ($possibleKeys as $key) {
                $value = $request->query($key) ?? $request->input($key);
                if ($value) {
                    $params[$paramName] = $value;
                    break;
                }
            }
        }

        return $params;
    }

    /**
     * Handle errors and return consistent error response
     *
     * @param Throwable $error
     * @return JsonResponse
     */
    private function handleError(Throwable $error): JsonResponse
    {
        // Get status code from exception message or default to 500
        $statusCode = 500;
        $message = $error->getMessage() ?? 'An error occurred';

        if (strpos($message, '404') !== false) {
            $statusCode = 404;
        } elseif (strpos($message, '405') !== false) {
            $statusCode = 405;
        } elseif (strpos($message, '403') !== false) {
            $statusCode = 403;
        } elseif (strpos($message, '401') !== false) {
            $statusCode = 401;
        } elseif (strpos($message, '422') !== false) {
            $statusCode = 422;
        }

        // In development, include stack trace
        $debugData = config('app.debug') ? [
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => collect($error->getTrace())->take(5)->toArray(),
        ] : null;

        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => $statusCode,
            'debug' => $debugData,
        ], $statusCode);
    }
}
