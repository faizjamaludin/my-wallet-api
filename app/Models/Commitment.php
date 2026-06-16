<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commitment extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'amount', 'due_day', 'is_paid', 'month'];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'due_day' => 'integer',
            'is_paid' => 'boolean',
        ];
    }
}
