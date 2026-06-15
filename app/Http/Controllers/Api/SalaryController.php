<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function show(Request $request)
    {
        $salary = Salary::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['salary' => 0, 'cc_budget' => 2500, 'grocery_budget' => 0, 'savings_target' => 0]
        );

        return response()->json($salary);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'salary' => 'sometimes|numeric|min:0',
            'cc_budget' => 'sometimes|numeric|min:0',
            'grocery_budget' => 'sometimes|numeric|min:0',
            'savings_target' => 'sometimes|numeric|min:0',
        ]);

        $salary = Salary::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json($salary);
    }
}
