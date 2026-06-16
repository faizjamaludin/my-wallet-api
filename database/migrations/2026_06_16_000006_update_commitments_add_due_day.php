<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->unsignedTinyInteger('due_day')->nullable()->after('amount'); // 1–31
            $table->dropColumn('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->dropColumn('due_day');
            $table->string('payment_method')->nullable()->after('amount');
        });
    }
};
