<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // The pump price new fill-ups are charged at, so drivers cannot type their own; null = not set.
            $table->decimal('fuel_price_per_litre', 8, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('fuel_price_per_litre');
        });
    }
};
