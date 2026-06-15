<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    protected $fillable = ['user_id', 'amount', 'note', 'date', 'child_fund_goal', 'child_fund_target_date'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'child_fund_goal' => 'float',
            'date' => 'date:Y-m-d',
            'child_fund_target_date' => 'date:Y-m-d',
        ];
    }
}
