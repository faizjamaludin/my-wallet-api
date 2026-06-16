<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rule extends Model
{
    protected $fillable = ['user_id', 'type', 'needs_pct', 'wants_pct', 'savings_pct'];

    protected function casts(): array
    {
        return [
            'needs_pct'   => 'decimal:2',
            'wants_pct'   => 'decimal:2',
            'savings_pct' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
