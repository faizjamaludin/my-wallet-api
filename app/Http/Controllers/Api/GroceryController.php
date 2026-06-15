<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grocery;
use Illuminate\Http\Request;

class GroceryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $groceries = Grocery::where('user_id', $request->user()->id)
            ->where('month', $month)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($groceries);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date_format:Y-m-d',
            'month' => 'required|date_format:Y-m',
        ]);

        $grocery = Grocery::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($grocery, 201);
    }

    public function destroy(Request $request, Grocery $grocery)
    {
        abort_if($grocery->user_id !== $request->user()->id, 403);
        $grocery->delete();

        return response()->noContent();
    }
}
