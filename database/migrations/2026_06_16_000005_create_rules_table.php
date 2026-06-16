<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['70-20-10', '50-30-20', 'custom'])->default('70-20-10');
            $table->decimal('needs_pct', 5, 2)->default(70);
            $table->decimal('wants_pct', 5, 2)->default(20);
            $table->decimal('savings_pct', 5, 2)->default(10);
            $table->timestamps();

            $table->unique('user_id'); // one rule config per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
