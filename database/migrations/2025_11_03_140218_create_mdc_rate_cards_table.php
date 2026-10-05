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
        Schema::create('mdc_rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('category_name'); // e.g., "Light Vehicle (3.5t - 9t)"
            $table->decimal('min_gvm_tonnes', 10, 2); // Minimum GVM in tonnes
            $table->decimal('max_gvm_tonnes', 10, 2)->nullable(); // Max GVM (null = unlimited)
            $table->decimal('rate_per_100km', 10, 2); // N$ per 100km (RFANAM rate)
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdc_rate_cards');
    }
};
