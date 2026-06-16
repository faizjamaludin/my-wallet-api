<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpendingEntry;
use Illuminate\Http\Request;

class SpendingController extends Controller
{
    public function index(Request $request)
    {
        $query = SpendingEntry::where('user_id', $request->user()->id)
            ->orderBy('date', 'desc');

        if ($request->has('month')) {
            $query->where('month', $request->month);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|max:500',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:debit,cash',
            'date' => 'required|date_format:Y-m-d',
            'month' => 'required|date_format:Y-m',
        ]);

        $entry = SpendingEntry::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return response()->json($entry, 201);
    }

    public function destroy(Request $request, SpendingEntry $spending)
    {
        abort_if($spending->user_id !== $request->user()->id, 403);
        $spending->delete();

        return response()->noContent();
    }
}
