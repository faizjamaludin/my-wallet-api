<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_card_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('budget', 10, 2)->default(2500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_card_budgets');
    }
};
