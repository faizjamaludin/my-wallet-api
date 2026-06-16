<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    public function index(Request $request)
    {
        $goals = SavingsGoal::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($goals);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'target_date' => 'nullable|date_format:Y-m-d',
        ]);

        $goal = SavingsGoal::create([
            ...$data,
            'user_id' => $request->user()->id,
            'saved_amount' => 0,
        ]);

        return response()->json($goal, 201);
    }

    public function update(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'target_amount' => 'sometimes|numeric|min:0',
            'target_date' => 'nullable|date_format:Y-m-d',
            'saved_amount' => 'sometimes|numeric|min:0',
        ]);

        $goal->update($data);

        return response()->json($goal);
    }

    public function destroy(Request $request, SavingsGoal $goal)
    {
        abort_if($goal->user_id !== $request->user()->id, 403);
        $goal->delete();

        return response()->noContent();
    }
}
