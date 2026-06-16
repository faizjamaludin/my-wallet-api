<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $cards = Card::where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get();

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:100',
            'type'            => 'required|in:credit,debit',
            'last_four'       => 'nullable|string|size:4',
            'credit_limit'    => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
        ]);

        $card = Card::create([...$data, 'user_id' => $request->user()->id]);

        return response()->json($card, 201);
    }

    public function update(Request $request, Card $card)
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'            => 'sometimes|string|max:100',
            'type'            => 'sometimes|in:credit,debit',
            'last_four'       => 'nullable|string|size:4',
            'credit_limit'    => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
        ]);

        $card->update($data);

        return response()->json($card);
    }

    public function destroy(Request $request, Card $card)
    {
        abort_unless($card->user_id === $request->user()->id, 403);

        $card->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
