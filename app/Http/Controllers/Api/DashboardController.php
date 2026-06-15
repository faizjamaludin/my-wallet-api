<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditCardBudget;
use App\Models\CreditCardTransaction;
use App\Models\Commitment;
use App\Models\Grocery;
use App\Models\Salary;
use App\Models\Saving;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $userId = $request->user()->id;

        $salary = Salary::where('user_id', $userId)->first();
        $salaryAmount = $salary?->salary ?? 0;
        $groceryBudget = $salary?->grocery_budget ?? 0;

        $ccBudgetRecord = CreditCardBudget::where('user_id', $userId)->first();
        $ccBudget = $ccBudgetRecord?->budget ?? 2500;

        $commitments = Commitment::where('user_id', $userId)->where('month', $month)->get();
        $totalCommitments = $commitments->sum('amount');
        $commitmentsPaid = $commitments->where('is_paid', true)->sum('amount');
        $commitmentsUnpaid = $commitments->where('is_paid', false)->sum('amount');

        $paidCCCommitments   = $commitments->where('payment_method', 'credit_card')->where('is_paid', true)->sum('amount');
        $unpaidCCCommitments = $commitments->where('payment_method', 'credit_card')->where('is_paid', false)->sum('amount');
        $debitCommitments    = $commitments->where('payment_method', '!=', 'credit_card')->sum('amount');

        $ccTransactions = CreditCardTransaction::where('user_id', $userId)
            ->where('month', $month)
            ->sum('amount');

        $ccSpent = $ccTransactions + $paidCCCommitments;

        $grocerySpent = Grocery::where('user_id', $userId)
            ->where('month', $month)
            ->sum('amount');

        $totalSavings = Saving::where('user_id', $userId)->sum('amount');

        $savingsThisMonth = Saving::where('user_id', $userId)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])
            ->sum('amount');

        $disposableBalance = $salaryAmount - $debitCommitments - $unpaidCCCommitments - $ccSpent - $grocerySpent;

        return response()->json([
            'month' => $month,
            'salary' => (float) $salaryAmount,
            'total_commitments' => (float) $totalCommitments,
            'commitments_paid' => (float) $commitmentsPaid,
            'commitments_unpaid' => (float) $commitmentsUnpaid,
            'cc_spent' => (float) $ccSpent,
            'cc_budget' => (float) $ccBudget,
            'grocery_spent' => (float) $grocerySpent,
            'grocery_budget' => (float) $groceryBudget,
            'savings_this_month' => (float) $savingsThisMonth,
            'total_savings' => (float) $totalSavings,
            'disposable_balance' => (float) $disposableBalance,
        ]);
    }
}
