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
        // SQLite (used by the test suite) has no MODIFY; rebuild the column instead.
        if (\DB::getDriverName() === 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('category')->default('fuel')->change();
            });

            return;
        }

        // For MySQL, we need to redefine the entire ENUM to add a new value
        \DB::statement("ALTER TABLE expenses MODIFY COLUMN category ENUM('fuel', 'maintenance', 'tolls', 'driver_wages', 'insurance', 'registration', 'repairs', 'mdc_payment', 'other') DEFAULT 'fuel'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\DB::getDriverName() === 'sqlite') {
            return;
        }

        // Remove mdc_payment from the enum
        \DB::statement("ALTER TABLE expenses MODIFY COLUMN category ENUM('fuel', 'maintenance', 'tolls', 'driver_wages', 'insurance', 'registration', 'repairs', 'other') DEFAULT 'fuel'");
    }
};
