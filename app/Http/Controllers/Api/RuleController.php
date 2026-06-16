<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function show(Request $request)
    {
        $rule = Rule::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['type' => '70-20-10', 'needs_pct' => 70, 'wants_pct' => 20, 'savings_pct' => 10]
        );

        return response()->json($rule);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'type'       => 'required|in:70-20-10,50-30-20,custom',
            'needs_pct'  => 'required|numeric|min:0|max:100',
            'wants_pct'  => 'required|numeric|min:0|max:100',
            'savings_pct'=> 'required|numeric|min:0|max:100',
        ]);

        // Percentages must sum to 100
        $sum = $data['needs_pct'] + $data['wants_pct'] + $data['savings_pct'];
        abort_if(abs($sum - 100) > 0.01, 422, 'Percentages must sum to 100');

        $rule = Rule::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json($rule);
    }
}
