<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commitment;
use App\Models\CreditCardTransaction;
use App\Models\Salary;
use App\Models\Saving;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuleCalculatorController extends Controller
{
    const RULES = [
        '50-30-20' => ['label' => '50/30/20', 'commitments' => 50, 'entertainment' => 30, 'savings' => 20],
        '60-20-20' => ['label' => '60/20/20', 'commitments' => 60, 'entertainment' => 20, 'savings' => 20],
        '70-20-10' => ['label' => '70/20/10', 'commitments' => 70, 'entertainment' => 20, 'savings' => 10],
    ];

    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'rule'  => ['required', 'string', 'in:50-30-20,60-20-20,70-20-10'],
            'month' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $month  = $request->query('month', now()->format('Y-m'));
        $userId = $request->user()->id;
        $rule   = self::RULES[$request->query('rule')];

        $salary = Salary::where('user_id', $userId)->first();

        if (! $salary || ! $salary->salary) {
            return response()->json([
                'message' => 'No salary record found. Please set your salary first.',
            ], 422);
        }

        $salaryAmount = (float) $salary->salary;

        $actualCommitments = (float) Commitment::where('user_id', $userId)
            ->where('month', $month)
            ->sum('amount');

        $actualEntertainment = (float) CreditCardTransaction::where('user_id', $userId)
            ->where('month', $month)
            ->sum('amount');

        $actualSavings = (float) Saving::where('user_id', $userId)
            ->whereRaw("TO_CHAR(date, 'YYYY-MM') = ?", [$month])
            ->sum('amount');

        $idealCommitments  = round($salaryAmount * $rule['commitments'] / 100, 2);
        $idealEntertainment = round($salaryAmount * $rule['entertainment'] / 100, 2);
        $idealSavings      = round($salaryAmount * $rule['savings'] / 100, 2);

        return response()->json([
            'rule'   => $rule['label'],
            'salary' => $salaryAmount,
            'month'  => $month,
            'categories' => [
                'commitments' => [
                    'label'        => 'Needs / Commitments',
                    'percentage'   => $rule['commitments'],
                    'ideal_amount' => $idealCommitments,
                    'actual_amount' => $actualCommitments,
                    'difference'   => round($idealCommitments - $actualCommitments, 2),
                    'status'       => $actualCommitments <= $idealCommitments ? 'under_budget' : 'over_budget',
                ],
                'entertainment' => [
                    'label'        => 'Wants / Entertainment',
                    'percentage'   => $rule['entertainment'],
                    'ideal_amount' => $idealEntertainment,
                    'actual_amount' => $actualEntertainment,
                    'difference'   => round($idealEntertainment - $actualEntertainment, 2),
                    'status'       => $actualEntertainment <= $idealEntertainment ? 'under_budget' : 'over_budget',
                ],
                'savings' => [
                    'label'        => 'Savings',
                    'percentage'   => $rule['savings'],
                    'ideal_amount' => $idealSavings,
                    'actual_amount' => $actualSavings,
                    'difference'   => round($actualSavings - $idealSavings, 2),
                    'status'       => $actualSavings >= $idealSavings ? 'on_track' : 'under_target',
                ],
            ],
        ]);
    }
}
