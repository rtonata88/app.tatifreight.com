<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fuel monitoring (phase 1): a fuel expense becomes a fill-up record, vehicles get a
 * consumption baseline and tank size, and failed checks are kept as fuel alerts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('driver_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('litres', 8, 2)->nullable()->after('amount');
            $table->decimal('price_per_litre', 8, 3)->nullable()->after('litres');
            $table->decimal('odometer', 10, 2)->nullable()->after('price_per_litre');
            $table->boolean('full_tank')->nullable()->after('odometer');
            $table->string('station')->nullable()->after('full_tank');
            $table->string('odometer_photo_path')->nullable()->after('receipt_path');
            // Full-to-full consumption worked out when the fill-up is saved; null for partial fills.
            $table->decimal('consumption_l_per_100km', 8, 2)->nullable()->after('station');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('expected_consumption', 6, 2)->nullable()->after('gvm_tonnes'); // L/100 km
            $table->decimal('tank_capacity', 8, 2)->nullable()->after('expected_consumption'); // litres
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('fuel_consumption_tolerance')->default(15); // % over expected
            $table->unsignedSmallInteger('fuel_min_km_between_fills')->default(150);
            $table->unsignedTinyInteger('fuel_price_tolerance')->default(10); // % over recent average
            $table->unsignedTinyInteger('fuel_trip_window_days')->default(2);
        });

        Schema::create('fuel_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('message');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note', 500)->nullable();
            $table->timestamps();

            $table->unique(['expense_id', 'type']);
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_alerts');

        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['fuel_consumption_tolerance', 'fuel_min_km_between_fills', 'fuel_price_tolerance', 'fuel_trip_window_days']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['expected_consumption', 'tank_capacity']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_id');
            $table->dropColumn(['litres', 'price_per_litre', 'odometer', 'full_tank', 'station', 'odometer_photo_path', 'consumption_l_per_100km']);
        });
    }
};
