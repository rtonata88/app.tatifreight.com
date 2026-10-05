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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedBigInteger('quote_id')->nullable(); // Foreign key will be added later
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('pickup_location')->nullable();
            $table->string('delivery_location')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('load_weight', 10, 2)->nullable(); // in tons
            $table->text('cargo_description')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // daily, weekly, monthly
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
