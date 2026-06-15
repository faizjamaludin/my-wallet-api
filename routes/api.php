<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommitmentController;
use App\Http\Controllers\Api\CreditCardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GroceryController;
use App\Http\Controllers\Api\RuleCalculatorController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\SavingController;
use Illuminate\Support\Facades\Route;

// Health check (Render uses this to confirm the service is up)
Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Auth (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Financial rule calculator
    Route::get('/rule-calculator', [RuleCalculatorController::class, 'calculate']);

    // Salary / paycheck settings
    Route::get('/salary', [SalaryController::class, 'show']);
    Route::put('/salary', [SalaryController::class, 'update']);

    // Commitments
    Route::get('/commitments', [CommitmentController::class, 'index']);
    Route::post('/commitments', [CommitmentController::class, 'store']);
    Route::put('/commitments/{commitment}', [CommitmentController::class, 'update']);
    Route::delete('/commitments/{commitment}', [CommitmentController::class, 'destroy']);
    Route::patch('/commitments/{commitment}/toggle-paid', [CommitmentController::class, 'togglePaid']);

    // Credit card
    Route::get('/credit-card/transactions', [CreditCardController::class, 'transactions']);
    Route::post('/credit-card/transactions', [CreditCardController::class, 'storeTransaction']);
    Route::delete('/credit-card/transactions/{transaction}', [CreditCardController::class, 'destroyTransaction']);
    Route::get('/credit-card/budget', [CreditCardController::class, 'getBudget']);
    Route::put('/credit-card/budget', [CreditCardController::class, 'updateBudget']);

    // Groceries
    Route::get('/groceries', [GroceryController::class, 'index']);
    Route::post('/groceries', [GroceryController::class, 'store']);
    Route::delete('/groceries/{grocery}', [GroceryController::class, 'destroy']);

    // Savings
    Route::get('/savings/summary', [SavingController::class, 'summary']);
    Route::put('/savings/child-fund', [SavingController::class, 'updateChildFund']);
    Route::get('/savings', [SavingController::class, 'index']);
    Route::post('/savings', [SavingController::class, 'store']);
    Route::put('/savings/{saving}', [SavingController::class, 'update']);
    Route::delete('/savings/{saving}', [SavingController::class, 'destroy']);
});
