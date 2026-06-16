<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    protected $fillable = ['user_id', 'name', 'target_amount', 'saved_amount', 'target_date'];

    protected function casts(): array
    {
        return [
            'target_amount' => 'float',
            'saved_amount' => 'float',
            'target_date' => 'date:Y-m-d',
        ];
    }
}
