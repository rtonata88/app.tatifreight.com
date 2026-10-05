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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // tipper, cooler, superlink, support
            $table->string('description')->nullable();
            $table->decimal('base_rate_hourly', 10, 2)->nullable();
            $table->decimal('base_rate_daily', 10, 2)->nullable();
            $table->decimal('base_rate_per_km', 10, 2)->nullable();
            $table->boolean('requires_mdc')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
