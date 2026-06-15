<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'payment_method', 'amount', 'is_paid', 'month'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'is_paid' => 'boolean',
        ];
    }
}
