<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AdvanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\SettlementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect based on authentication status
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    // Unauthenticated users are redirected to login page
    return redirect()->route('login');
})->name('home');

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
    Route::post('/splits/{split}/mark-paid', [PaymentController::class, 'markPaid'])->name('splits.mark-paid');
    Route::put('/splits/{split}/mark-paid', [PaymentController::class, 'markPaid'])->name('splits.mark-paid.update');
    Route::get('/groups/{group}/payments', [PaymentController::class, 'groupPaymentHistory'])->name('groups.payments.history');
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
    Route::delete('/groups/{group}/members/{member}', [GroupController::class, 'removeMember'])->name('groups.members.remove');
    Route::delete('/groups/{group}/leave', [GroupController::class, 'leaveGroup'])->name('groups.members.leave');

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

    // Settlement Management (nested under groups)
    Route::post('/groups/{group}/settlements/confirm', [SettlementController::class, 'confirmSettlement'])->name('groups.settlements.confirm');
    Route::get('/groups/{group}/settlements/history', [SettlementController::class, 'getSettlementHistory'])->name('groups.settlements.history');
    Route::get('/groups/{group}/settlements/unsettled', [SettlementController::class, 'getUnsettledTransactions'])->name('groups.settlements.unsettled');

    // Attachments Management
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/attachments/{attachment}/show', [AttachmentController::class, 'show'])->name('attachments.show');

    // PIN Management
    Route::get('/auth/update-pin', [AuthController::class, 'showUpdatePin'])->name('auth.show-update-pin');
    Route::put('/auth/update-pin', [AuthController::class, 'updatePin'])->name('auth.update-pin');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
