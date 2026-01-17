<?php

namespace App\Services;

use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdvanceController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * API Dispatcher Service
 *
 * Maps API method names to controller actions
 * Provides centralized routing for web view and mobile apps
 * Returns consistent JSON responses
 */
class ApiDispatcherService
{
    /**
     * Method to controller action mapping
     *
     * Format: 'method_name' => [ControllerClass::class, 'methodName', 'http_method']
     */
    private array $methodMap = [
        // Groups Methods
        'groups.list' => [GroupController::class, 'apiIndex', 'GET'],
        'groups.get' => [GroupController::class, 'apiShow', 'GET'],
        'groups.create' => [GroupController::class, 'apiStore', 'POST'],
        'groups.update' => [GroupController::class, 'apiUpdate', 'PUT'],
        'groups.delete' => [GroupController::class, 'apiDestroy', 'DELETE'],
        'groups.members.list' => [GroupController::class, 'apiMembers', 'GET'],
        'groups.members.add' => [GroupController::class, 'apiAddMember', 'POST'],
        'groups.members.remove' => [GroupController::class, 'removeMember', 'DELETE'],
        'groups.leave' => [GroupController::class, 'leaveGroup', 'DELETE'],

        // Expenses Methods
        'expenses.list' => [ExpenseController::class, 'apiIndex', 'GET'],
        'expenses.get' => [ExpenseController::class, 'show', 'GET'],
        'expenses.create' => [ExpenseController::class, 'apiStore', 'POST'],
        'expenses.update' => [ExpenseController::class, 'apiUpdate', 'PUT'],
        'expenses.delete' => [ExpenseController::class, 'destroy', 'DELETE'],

        // Payments Methods
        'payments.pending' => [PaymentController::class, 'groupPaymentHistory', 'GET'],
        'payments.mark-paid' => [PaymentController::class, 'apiMarkPayment', 'POST'],
        'payments.mark-paid-batch' => [PaymentController::class, 'apiMarkPaidBatch', 'POST'],
        'payments.settlement-details' => [PaymentController::class, 'settlementDetails', 'GET'],
        'payments.history' => [PaymentController::class, 'apiPaymentHistory', 'GET'],
        'payments.recent-activities' => [PaymentController::class, 'apiRecentActivities', 'GET'],
        'transactions.history' => [PaymentController::class, 'apiTransactionHistory', 'GET'],

        // Dashboard Methods
        'dashboard.index' => [DashboardController::class, 'apiIndex', 'GET'],
        'dashboard.group' => [DashboardController::class, 'apiGroupDashboard', 'GET'],
        'dashboard.summary' => [DashboardController::class, 'groupSummary', 'GET'],

        // Advances Methods
        'advances.add' => [AdvanceController::class, 'apiStore', 'POST'],

        // Notifications Methods
        'notifications.list' => [NotificationController::class, 'index', 'GET'],
        'notifications.unread-count' => [NotificationController::class, 'unreadCount', 'GET'],
        'notifications.mark-read' => [NotificationController::class, 'markAsRead', 'POST'],
        'notifications.mark-all-read' => [NotificationController::class, 'markAllAsRead', 'POST'],

        // Auth Methods
        'auth.update-pin' => [ApiAuthController::class, 'updatePin', 'POST'],
    ];

    /**
     * Dispatch API method call
     *
     * @param string $method API method name (e.g., 'groups.list', 'expenses.create')
     * @param Request $request HTTP request object
     * @param array $params Route parameters (e.g., ['group' => 1, 'expense' => 5])
     * @return array Response data
     * @throws Exception If method not found or execution fails
     */
    public function dispatch(string $method, Request $request, array $params = []): array
    {
        // Check if method is registered
        if (!isset($this->methodMap[$method])) {
            throw new Exception("API method '{$method}' not found", 404);
        }

        [$controllerClass, $methodName, $httpMethod] = $this->methodMap[$method];

        try {
            // Validate HTTP method matches
            if (strtoupper($request->getMethod()) !== $httpMethod && $httpMethod !== 'ANY') {
                throw new Exception("HTTP method mismatch. Expected {$httpMethod}, got {$request->getMethod()}", 405);
            }

            // Instantiate controller
            $controller = app($controllerClass);

            // Build call parameters
            $callParams = $this->buildMethodParameters($method, $request, $params);

            // Execute controller method
            $result = call_user_func_array([$controller, $methodName], $callParams);

            // Handle various response types
            return $this->normalizeResponse($result);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Build parameters for controller method call
     *
     * @param string $method API method name
     * @param Request $request HTTP request
     * @param array $params Route parameters
     * @return array Parameters to pass to controller method
     */
    private function buildMethodParameters(string $method, Request $request, array $params): array
    {
        $callParams = [];

        // Methods that don't require Request as first parameter (they receive model objects instead)
        $noRequestMethods = [
            'groups.members.remove',
            'groups.leave',
            'expenses.get',
            'groups.delete',
            'expenses.delete',
        ];

        // Only add Request for methods that need it
        if (!in_array($method, $noRequestMethods)) {
            $callParams[] = $request;
        }

        // Add model parameters from route
        foreach ($params as $paramName => $paramValue) {
            // Resolve model from container
            $paramClass = $this->resolveModelClass($paramName);
            if ($paramClass && $paramValue) {
                $model = $paramClass::find($paramValue);
                if (!$model) {
                    throw new Exception("Resource not found: {$paramName} = {$paramValue}", 404);
                }
                $callParams[] = $model;
            }
        }

        return $callParams;
    }

    /**
     * Resolve model class from parameter name
     *
     * @param string $paramName Parameter name (e.g., 'group', 'expense')
     * @return string|null Model class path
     */
    private function resolveModelClass(string $paramName): ?string
    {
        $modelMap = [
            'group' => \App\Models\Group::class,
            'expense' => \App\Models\Expense::class,
            'payment' => \App\Models\Payment::class,
            'user' => \App\Models\User::class,
            'notification' => \App\Models\Notification::class,
        ];

        return $modelMap[$paramName] ?? null;
    }

    /**
     * Normalize controller response to consistent JSON format
     *
     * @param mixed $result Controller method result
     * @return array Normalized response
     */
    private function normalizeResponse($result): array
    {
        // Already an array with 'success' key
        if (is_array($result) && isset($result['success'])) {
            return $result;
        }

        // Response object (view/redirect)
        if (is_object($result) && method_exists($result, 'status')) {
            $statusCode = $result->status();
            return [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status' => $statusCode,
                'data' => $result->original ?? null,
            ];
        }

        // Eloquent model or collection
        if (is_object($result)) {
            return [
                'success' => true,
                'data' => $result,
            ];
        }

        // Fallback
        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * Get available API methods
     *
     * @return array List of available methods
     */
    public function getAvailableMethods(): array
    {
        return array_keys($this->methodMap);
    }

    /**
     * Register custom method mapping
     *
     * @param string $method Method name
     * @param string $controller Controller class
     * @param string $action Controller action method
     * @param string $httpMethod HTTP method
     */
    public function registerMethod(string $method, string $controller, string $action, string $httpMethod = 'ANY'): void
    {
        $this->methodMap[$method] = [$controller, $action, $httpMethod];
    }
}
