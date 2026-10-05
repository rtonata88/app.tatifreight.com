<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make legacy tare_weight, load_weight, and total_mass fields nullable
     * since the new RFANAM MDC system uses GVM-based rates instead.
     */
    public function up(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->decimal('tare_weight', 10, 2)->nullable()->change();
            $table->decimal('load_weight', 10, 2)->nullable()->change();
            $table->decimal('total_mass', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->decimal('tare_weight', 10, 2)->nullable(false)->change();
            $table->decimal('load_weight', 10, 2)->nullable(false)->change();
            $table->decimal('total_mass', 10, 2)->nullable(false)->change();
        });
    }
};
