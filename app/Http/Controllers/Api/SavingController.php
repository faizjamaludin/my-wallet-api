<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Saving;
use Illuminate\Http\Request;

class SavingController extends Controller
{
    public function index(Request $request)
    {
        $savings = Saving::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($savings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
            'date' => 'required|date_format:Y-m-d',
            'goal_id' => 'nullable|exists:savings_goals,id',
            'child_fund_goal' => 'nullable|numeric|min:0',
            'child_fund_target_date' => 'nullable|date_format:Y-m-d',
        ]);

        $saving = Saving::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($saving, 201);
    }

    public function update(Request $request, Saving $saving)
    {
        abort_if($saving->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'note' => 'nullable|string|max:500',
            'date' => 'sometimes|date_format:Y-m-d',
            'child_fund_goal' => 'nullable|numeric|min:0',
            'child_fund_target_date' => 'nullable|date_format:Y-m-d',
        ]);

        $saving->update($data);

        return response()->json($saving);
    }

    public function summary(Request $request)
    {
        $userId = $request->user()->id;
        $currentMonth = now()->format('Y-m');

        $totalSavings = Saving::where('user_id', $userId)->sum('amount');

        $savingsThisMonth = Saving::where('user_id', $userId)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('amount');

        $latest = Saving::where('user_id', $userId)->latest('date')->first();

        return response()->json([
            'total_savings' => (float) $totalSavings,
            'savings_this_month' => (float) $savingsThisMonth,
            'child_fund_goal' => $latest?->child_fund_goal,
            'child_fund_target_date' => $latest?->child_fund_target_date?->format('Y-m-d'),
        ]);
    }

    public function destroy(Request $request, Saving $saving)
    {
        abort_if($saving->user_id !== $request->user()->id, 403);
        $saving->delete();

        return response()->noContent();
    }

    public function updateChildFund(Request $request)
    {
        $data = $request->validate([
            'child_fund_goal' => 'required|numeric|min:0',
            'child_fund_target_date' => 'required|date_format:Y-m-d',
        ]);

        Saving::where('user_id', $request->user()->id)
            ->update([
                'child_fund_goal' => $data['child_fund_goal'],
                'child_fund_target_date' => $data['child_fund_target_date'],
            ]);

        return response()->json($data);
    }
}
