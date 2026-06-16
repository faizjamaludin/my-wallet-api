<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommitmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RuleController;
use App\Http\Controllers\Api\SavingController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\SpendingController;
use Illuminate\Support\Facades\Route;

// Health check (Render uses this to confirm the service is up)
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Cards
    Route::get('/cards', [CardController::class, 'index']);
    Route::post('/cards', [CardController::class, 'store']);
    Route::put('/cards/{card}', [CardController::class, 'update']);
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);
    Route::post('/transactions/import', [TransactionController::class, 'import']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Commitments
    Route::get('/commitments', [CommitmentController::class, 'index']);
    Route::post('/commitments', [CommitmentController::class, 'store']);
    Route::put('/commitments/{commitment}', [CommitmentController::class, 'update']);
    Route::delete('/commitments/{commitment}', [CommitmentController::class, 'destroy']);
    Route::patch('/commitments/{commitment}/toggle-paid', [CommitmentController::class, 'togglePaid']);

    // Budgets
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);

    // Financial rules
    Route::get('/rules', [RuleController::class, 'show']);
    Route::put('/rules', [RuleController::class, 'update']);

    // Daily spending
    Route::get('/spending', [SpendingController::class, 'index']);
    Route::post('/spending', [SpendingController::class, 'store']);
    Route::delete('/spending/{spending}', [SpendingController::class, 'destroy']);

    // Savings goals (must be before wildcard savings routes)
    Route::get('/savings/goals', [SavingsGoalController::class, 'index']);
    Route::post('/savings/goals', [SavingsGoalController::class, 'store']);
    Route::put('/savings/goals/{goal}', [SavingsGoalController::class, 'update']);
    Route::delete('/savings/goals/{goal}', [SavingsGoalController::class, 'destroy']);

    // Savings entries
    Route::get('/savings/summary', [SavingController::class, 'summary']);
    Route::put('/savings/child-fund', [SavingController::class, 'updateChildFund']);
    Route::get('/savings', [SavingController::class, 'index']);
    Route::post('/savings', [SavingController::class, 'store']);
    Route::put('/savings/{saving}', [SavingController::class, 'update']);
    Route::delete('/savings/{saving}', [SavingController::class, 'destroy']);
});
