<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCardTransaction extends Model
{
    protected $fillable = ['user_id', 'description', 'amount', 'category', 'date', 'month'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }
}
