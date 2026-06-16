<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('month', 7);           // YYYY-MM, denormalised for fast queries
            $table->string('description')->nullable();
            $table->string('merchant')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'month']);
            $table->index(['user_id', 'card_id']);
            $table->index(['user_id', 'category_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
