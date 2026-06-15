<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grocery extends Model
{
    protected $fillable = ['user_id', 'store', 'amount', 'date', 'month'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }
}
