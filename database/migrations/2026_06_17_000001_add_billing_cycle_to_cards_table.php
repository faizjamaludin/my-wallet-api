<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Day of month (1–31) when the statement is generated
            $table->tinyInteger('statement_day')->default(18)->after('current_balance');
            // Day of month (1–31) when payment is due
            $table->tinyInteger('payment_day')->default(1)->after('statement_day');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['statement_day', 'payment_day']);
        });
    }
};
