<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCardBudget extends Model
{
    protected $fillable = ['user_id', 'budget'];

    protected function casts(): array
    {
        return [
            'budget' => 'float',
        ];
    }
}
