<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'last_four', 'credit_limit', 'current_balance',
        'statement_day', 'payment_day',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit'    => 'decimal:2',
            'current_balance' => 'decimal:2',
            'statement_day'   => 'integer',
            'payment_day'     => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
