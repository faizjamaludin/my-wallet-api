<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpendingEntry extends Model
{
    protected $fillable = ['user_id', 'description', 'category', 'amount', 'payment_method', 'date', 'month'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }
}
