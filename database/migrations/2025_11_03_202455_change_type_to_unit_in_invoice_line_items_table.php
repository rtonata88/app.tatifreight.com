<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Replace 'type' field with 'unit' field for transport/logistics industry
     */
    public function up(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->enum('unit', [
                'trip',        // Single trip/journey
                'day',         // Per day rental
                'hour',        // Per hour rental
                'km',          // Per kilometer
                'tonne',       // Per tonne of cargo
                'load',        // Per load
                'pallet',      // Per pallet
                'container',   // Per container (20ft/40ft)
                'cbm',         // Cubic meter
                'item',        // Per item
                'month',       // Monthly rental
                'week',        // Weekly rental
            ])->default('trip')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->enum('type', ['rental', 'mdc', 'extra', 'discount'])->default('rental')->after('amount');
        });
    }
};

