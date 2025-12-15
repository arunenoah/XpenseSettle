<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AdvanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceivedPaymentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

// Root route - redirect to login for guests, dashboard for authenticated users
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Landing page (optional marketing page)
Route::get('/landing', function () {
    return view('home');
})->name('landing');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Payments & Splits
    Route::post('/payments/{payment}/mark-paid', [PaymentController::class, 'markPayment'])->name('payments.mark-paid');
    Route::put('/payments/{payment}/mark-paid', [PaymentController::class, 'markPayment'])->name('payments.mark-paid.update');
    Route::post('/payments/mark-paid-batch', [PaymentController::class, 'markPaidBatch'])->name('payments.mark-paid-batch');
    Route::post('/splits/{split}/mark-paid', [PaymentController::class, 'markPaid'])->name('splits.mark-paid');
    Route::put('/splits/{split}/mark-paid', [PaymentController::class, 'markPaid'])->name('splits.mark-paid.update');
    Route::get('/groups/{group}/payments', [PaymentController::class, 'groupPaymentHistory'])->name('groups.payments.history');
    Route::get('/groups/{group}/payments/export-pdf', [PaymentController::class, 'exportHistoryPdf'])->name('groups.payments.export-pdf');
    Route::get('/groups/{group}/payments/debug/{user}', [PaymentController::class, 'debugSettlement'])->name('groups.payments.debug');
    Route::get('/groups/{group}/dashboard', [DashboardController::class, 'groupDashboard'])->name('groups.dashboard');
    Route::get('/groups/{group}/summary', [DashboardController::class, 'groupSummary'])->name('groups.summary');
    Route::get('/groups/{group}/timeline/pdf', [DashboardController::class, 'exportTimelinePdf'])->name('groups.timeline.pdf');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Groups Management
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    // Group Members Management
    Route::get('/groups/{group}/members', [GroupController::class, 'members'])->name('groups.members');
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember'])->name('groups.members.add');
    Route::post('/groups/{group}/contacts', [GroupController::class, 'addContact'])->name('groups.contacts.add');
    Route::patch('/groups/{group}/members/{member}/family-count', [GroupController::class, 'updateFamilyCount'])->name('groups.members.update-family-count');
    Route::patch('/groups/{group}/contacts/{contact}/family-count', [GroupController::class, 'updateContactFamilyCount'])->name('groups.contacts.update-family-count');
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::delete('/groups/{group}/leave', [GroupController::class, 'leaveGroup'])->name('groups.members.leave');
    
    // Plan Management
    Route::post('/groups/{group}/increment-ocr', [GroupController::class, 'incrementOCR'])->name('groups.increment-ocr');
    
    // Testing: Manual plan activation (remove in production)
    Route::post('/groups/{group}/activate-trip-pass', [GroupController::class, 'activateTripPass'])->name('groups.activate-trip-pass');
    Route::post('/user/activate-lifetime', [GroupController::class, 'activateLifetime'])->name('user.activate-lifetime');

    // Expenses Management (nested under groups)
    Route::get('/groups/{group}/expenses/create', [ExpenseController::class, 'create'])->name('groups.expenses.create');
    Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store'])->name('groups.expenses.store');
    Route::get('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'show'])->name('groups.expenses.show');
    Route::get('/groups/{group}/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('groups.expenses.edit');
    Route::put('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'update'])->name('groups.expenses.update');
    Route::delete('/groups/{group}/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('groups.expenses.destroy');

    // Advances Management (nested under groups)
    Route::post('/groups/{group}/advances', [AdvanceController::class, 'store'])->name('groups.advances.store');
    Route::delete('/groups/{group}/advances/{advance}', [AdvanceController::class, 'destroy'])->name('groups.advances.destroy');

    // Received Payments Management (nested under groups)
    Route::post('/groups/{group}/received-payments', [ReceivedPaymentController::class, 'store'])->name('groups.received-payments.store');
    Route::delete('/groups/{group}/received-payments/{receivedPayment}', [ReceivedPaymentController::class, 'destroy'])->name('groups.received-payments.destroy');
    Route::get('/groups/{group}/members/{user}/received-payments', [ReceivedPaymentController::class, 'getForMember'])->name('groups.received-payments.member');
    Route::get('/groups/{group}/payments/member/{member}/received-payments', [PaymentController::class, 'getReceivedPayments'])->name('groups.payments.member-received-payments');

    // Settlement Management (nested under groups)
    Route::post('/groups/{group}/settlements/confirm', [SettlementController::class, 'confirmSettlement'])->name('groups.settlements.confirm');
    Route::post('/groups/{group}/manual-settle', [PaymentController::class, 'manualSettle'])->name('groups.manual-settle');
    Route::get('/groups/{group}/settlements/history', [SettlementController::class, 'getSettlementHistory'])->name('groups.settlements.history');
    Route::get('/groups/{group}/settlements/unsettled', [SettlementController::class, 'getUnsettledTransactions'])->name('groups.settlements.unsettled');

    // Attachments Management
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/attachments/{attachment}/show', [AttachmentController::class, 'show'])->name('attachments.show');

    // PIN Management
    Route::get('/auth/update-pin', [AuthController::class, 'showUpdatePin'])->name('auth.show-update-pin');
    Route::put('/auth/update-pin', [AuthController::class, 'updatePin'])->name('auth.update-pin');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Audit Logs (only group admins can view their group's logs)
    Route::get('/groups/{group}/audit-logs', [AuditLogController::class, 'groupAuditLogs'])->name('groups.audit-logs');
    Route::get('/groups/{group}/audit-logs/filter', [AuditLogController::class, 'filterByAction'])->name('groups.audit-logs.filter');
    Route::get('/groups/{group}/audit-logs/export-csv', [AuditLogController::class, 'exportCsv'])->name('admin.audit-logs.export');
});

// Super Admin Routes (only for arun@example.com)
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    // PIN Verification
    Route::get('/verify', [\App\Http\Controllers\AdminController::class, 'showPinVerification'])->name('verify');
    Route::post('/verify', [\App\Http\Controllers\AdminController::class, 'verifyPin'])->name('verify.submit');
    Route::post('/logout', [\App\Http\Controllers\AdminController::class, 'logout'])->name('logout');
    
    // Admin Dashboard (requires PIN verification)
    Route::get('/', [\App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
    Route::post('/users/{user}/plan', [\App\Http\Controllers\AdminController::class, 'updateUserPlan'])->name('users.update-plan');
    Route::post('/groups/{group}/plan', [\App\Http\Controllers\AdminController::class, 'updateGroupPlan'])->name('groups.update-plan');
    Route::post('/groups/{group}/reset-ocr', [\App\Http\Controllers\AdminController::class, 'resetOCRCounter'])->name('groups.reset-ocr');
});
