<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings', function (Blueprint $table) {
            $table->foreignId('goal_id')->nullable()->constrained('savings_goals')->nullOnDelete()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('savings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\SavingsGoal::class, 'goal_id');
            $table->dropColumn('goal_id');
        });
    }
};
