<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            // Check if booking_id exists and drop it
            if (Schema::hasColumn('mdc_calculations', 'booking_id')) {
                if (DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_NAME', 'mdc_calculations')
                    ->where('COLUMN_NAME', 'booking_id')
                    ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                    ->exists()) {
                    $table->dropForeign(['booking_id']);
                }
                $table->dropColumn('booking_id');
            }
            
            // Add logbook_id column if it doesn't exist
            if (!Schema::hasColumn('mdc_calculations', 'logbook_id')) {
                $table->foreignId('logbook_id')->after('id')->constrained()->onDelete('cascade');
            } else {
                // Column exists but may need foreign key
                if (!DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_NAME', 'mdc_calculations')
                    ->where('COLUMN_NAME', 'logbook_id')
                    ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                    ->exists()) {
                    $table->foreign('logbook_id')->references('id')->on('logbooks')->onDelete('cascade');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mdc_calculations', function (Blueprint $table) {
            // Drop the foreign key constraint and column for logbook_id if they exist
            if (Schema::hasColumn('mdc_calculations', 'logbook_id')) {
                if (DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_NAME', 'mdc_calculations')
                    ->where('COLUMN_NAME', 'logbook_id')
                    ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                    ->exists()) {
                    $table->dropForeign(['logbook_id']);
                }
                $table->dropColumn('logbook_id');
            }
            
            // Restore booking_id column with foreign key constraint if it doesn't exist
            if (!Schema::hasColumn('mdc_calculations', 'booking_id')) {
                $table->foreignId('booking_id')->after('id')->constrained()->onDelete('cascade');
            }
        });
    }
};
