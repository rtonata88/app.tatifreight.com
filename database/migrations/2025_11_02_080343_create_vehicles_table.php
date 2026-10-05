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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained()->onDelete('restrict');
            $table->string('reg_number')->unique();
            $table->string('vin')->nullable()->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year')->nullable();
            $table->decimal('load_capacity', 10, 2)->nullable(); // in tons
            $table->decimal('tare_weight', 10, 2)->nullable(); // in tons
            $table->date('insurance_expiry')->nullable();
            $table->date('disc_expiry')->nullable();
            $table->date('roadworthy_expiry')->nullable();
            $table->enum('status', ['available', 'in_use', 'maintenance', 'retired'])->default('available');
            $table->decimal('current_mileage', 10, 2)->default(0);
            $table->string('gps_device_id')->nullable();
            $table->string('license_disc_path')->nullable();
            $table->string('insurance_path')->nullable();
            $table->json('photos')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
