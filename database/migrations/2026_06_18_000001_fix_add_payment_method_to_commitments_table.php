<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('commitments', 'payment_method')) {
            Schema::table('commitments', function (Blueprint $table) {
                $table->enum('payment_method', ['debit', 'credit_card'])->default('debit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('commitments', 'payment_method')) {
            Schema::table('commitments', function (Blueprint $table) {
                $table->dropColumn('payment_method');
            });
        }
    }
};
