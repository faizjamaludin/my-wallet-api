<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $budgets = Budget::with('category')
            ->where('user_id', $request->user()->id)
            ->where('month', $month)
            ->get();

        return response()->json($budgets);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount'      => 'required|numeric|min:0',
            'month'       => 'required|date_format:Y-m',
        ]);

        $budget = Budget::updateOrCreate(
            [
                'user_id'     => $request->user()->id,
                'category_id' => $data['category_id'],
                'month'       => $data['month'],
            ],
            ['amount' => $data['amount']]
        );

        $budget->load('category');

        return response()->json($budget, 201);
    }
}
