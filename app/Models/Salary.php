<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = ['user_id', 'salary', 'cc_budget', 'grocery_budget', 'savings_target'];

    protected function casts(): array
    {
        return [
            'salary' => 'float',
            'cc_budget' => 'float',
            'grocery_budget' => 'float',
            'savings_target' => 'float',
        ];
    }
}
