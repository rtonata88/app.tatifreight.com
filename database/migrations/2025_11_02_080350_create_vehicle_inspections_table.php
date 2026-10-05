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
        Schema::create('vehicle_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('inspector_id')->constrained('users')->onDelete('restrict');
            $table->date('inspection_date');
            $table->enum('inspection_type', ['pre_trip', 'post_trip', 'maintenance', 'annual'])->default('pre_trip');
            $table->decimal('mileage', 10, 2);
            $table->json('checklist')->nullable(); // JSON field for checklist items
            $table->boolean('passed')->default(true);
            $table->text('issues_found')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('photos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspections');
    }
};
