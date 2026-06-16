<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spending_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('category');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('debit');
            $table->date('date');
            $table->string('month', 7);
            $table->timestamps();
            $table->index(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spending_entries');
    }
};
