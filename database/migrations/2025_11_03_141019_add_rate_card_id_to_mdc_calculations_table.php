<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->foreignId('mdc_rate_card_id')->nullable()->after('vehicle_id')->constrained()->onDelete('set null');
            $table->decimal('gvm_tonnes', 10, 2)->nullable()->after('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            $table->dropForeign(['mdc_rate_card_id']);
            $table->dropColumn(['mdc_rate_card_id', 'gvm_tonnes']);
        });
    }
};
