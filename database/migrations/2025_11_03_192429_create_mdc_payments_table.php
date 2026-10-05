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
        Schema::create('mdc_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('bank_transfer'); // bank_transfer, cash, eft, cheque
            $table->string('bank_reference')->nullable();
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete(); // Auto-created expense
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Pivot table to link payments to specific MDC calculations
        Schema::create('mdc_calculation_payment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mdc_calculation_id')->constrained()->onDelete('cascade');
            $table->foreignId('mdc_payment_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_allocated', 12, 2); // How much of this payment goes to this calculation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdc_calculation_payment');
        Schema::dropIfExists('mdc_payments');
    }
};
