<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditCardBudget;
use App\Models\CreditCardTransaction;
use Illuminate\Http\Request;

class CreditCardController extends Controller
{
    public function transactions(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $transactions = CreditCardTransaction::where('user_id', $request->user()->id)
            ->where('month', $month)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($transactions);
    }

    public function storeTransaction(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'date' => 'required|date_format:Y-m-d',
            'month' => 'required|date_format:Y-m',
        ]);

        $transaction = CreditCardTransaction::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($transaction, 201);
    }

    public function destroyTransaction(Request $request, CreditCardTransaction $transaction)
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);
        $transaction->delete();

        return response()->noContent();
    }

    public function getBudget(Request $request)
    {
        $budget = CreditCardBudget::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['budget' => 2500]
        );

        return response()->json(['budget' => $budget->budget]);
    }

    public function updateBudget(Request $request)
    {
        $data = $request->validate([
            'budget' => 'required|numeric|min:0',
        ]);

        $budget = CreditCardBudget::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['budget' => $data['budget']]
        );

        return response()->json(['budget' => $budget->budget]);
    }
}
