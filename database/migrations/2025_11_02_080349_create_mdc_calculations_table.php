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
        Schema::create('mdc_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict');
            $table->decimal('distance_km', 10, 2);
            $table->decimal('tare_weight', 10, 2); // in kg
            $table->decimal('load_weight', 10, 2); // in kg
            $table->decimal('total_mass', 10, 2); // in kg
            $table->decimal('rate_per_100kg_km', 10, 4); // government rate
            $table->decimal('mdc_amount', 12, 2);
            $table->date('calculation_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdc_calculations');
    }
};
