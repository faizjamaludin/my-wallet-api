<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commitment;
use Illuminate\Http\Request;

class CommitmentController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));

        $commitments = Commitment::where('user_id', $request->user()->id)
            ->where('month', $month)
            ->orderBy('created_at')
            ->get();

        return response()->json($commitments);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'type'           => 'required|in:fixed,variable',
            'payment_method' => 'nullable|in:debit,credit_card',
            'amount'         => 'required|numeric|min:0',
            'due_day'        => 'nullable|integer|min:1|max:31',
            'is_paid'        => 'boolean',
            'month'          => 'required|date_format:Y-m',
        ]);

        $commitment = Commitment::create([
            ...$data,
            'user_id' => $request->user()->id,
            'is_paid' => $data['is_paid'] ?? false,
        ]);

        return response()->json($commitment, 201);
    }

    public function update(Request $request, Commitment $commitment)
    {
        $this->authorizeOwnership($request, $commitment);

        $data = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'type'           => 'sometimes|in:fixed,variable',
            'payment_method' => 'sometimes|in:debit,credit_card',
            'amount'         => 'sometimes|numeric|min:0',
            'due_day'        => 'nullable|integer|min:1|max:31',
            'is_paid'        => 'sometimes|boolean',
            'month'          => 'sometimes|date_format:Y-m',
        ]);

        $commitment->update($data);

        return response()->json($commitment);
    }

    public function destroy(Request $request, Commitment $commitment)
    {
        $this->authorizeOwnership($request, $commitment);
        $commitment->delete();

        return response()->noContent();
    }

    public function togglePaid(Request $request, Commitment $commitment)
    {
        $this->authorizeOwnership($request, $commitment);
        $commitment->update(['is_paid' => ! $commitment->is_paid]);

        return response()->json(['id' => $commitment->id, 'is_paid' => $commitment->is_paid]);
    }

    private function authorizeOwnership(Request $request, Commitment $commitment): void
    {
        abort_if($commitment->user_id !== $request->user()->id, 403);
    }
}
