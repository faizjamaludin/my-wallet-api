<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = preset
            $table->string('name');
            $table->string('slug')->nullable();
            $table->enum('type', ['preset', 'custom'])->default('custom');
            $table->string('color', 7)->nullable();  // hex e.g. #CCFB89
            $table->string('icon')->nullable();       // lucide icon name
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
