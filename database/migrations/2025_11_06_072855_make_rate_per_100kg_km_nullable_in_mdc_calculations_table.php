<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make rate_per_100kg_km nullable since it's a legacy field
     * and the new RFANAM MDC system uses mdc_rate_card_id instead.
     */
    public function up(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->decimal('rate_per_100kg_km', 10, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->decimal('rate_per_100kg_km', 10, 4)->nullable(false)->change();
        });
    }
};
