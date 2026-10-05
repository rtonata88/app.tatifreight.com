<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Whether the column has a foreign key. Uses the schema builder so it
     * works on MySQL and SQLite (the test suite) alike.
     */
    private function hasForeignKey(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasBookingId = Schema::hasColumn('mdc_calculations', 'booking_id');
        $bookingHasForeign = $hasBookingId && $this->hasForeignKey('mdc_calculations', 'booking_id');
        $hasLogbookId = Schema::hasColumn('mdc_calculations', 'logbook_id');
        $logbookHasForeign = $hasLogbookId && $this->hasForeignKey('mdc_calculations', 'logbook_id');

        Schema::table('mdc_calculations', function (Blueprint $table) use ($hasBookingId, $bookingHasForeign, $hasLogbookId, $logbookHasForeign) {
            // Check if booking_id exists and drop it
            if ($hasBookingId) {
                if ($bookingHasForeign) {
                    $table->dropForeign(['booking_id']);
                }
                $table->dropColumn('booking_id');
            }

            // Add logbook_id column if it doesn't exist
            if (! $hasLogbookId) {
                $table->foreignId('logbook_id')->after('id')->constrained()->onDelete('cascade');
            } elseif (! $logbookHasForeign) {
                // Column exists but may need foreign key
                $table->foreign('logbook_id')->references('id')->on('logbooks')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasLogbookId = Schema::hasColumn('mdc_calculations', 'logbook_id');
        $logbookHasForeign = $hasLogbookId && $this->hasForeignKey('mdc_calculations', 'logbook_id');
        $hasBookingId = Schema::hasColumn('mdc_calculations', 'booking_id');

        Schema::table('mdc_calculations', function (Blueprint $table) use ($hasLogbookId, $logbookHasForeign, $hasBookingId) {
            // Drop the foreign key constraint and column for logbook_id if they exist
            if ($hasLogbookId) {
                if ($logbookHasForeign) {
                    $table->dropForeign(['logbook_id']);
                }
                $table->dropColumn('logbook_id');
            }

            // Restore booking_id column with foreign key constraint if it doesn't exist
            if (! $hasBookingId) {
                $table->foreignId('booking_id')->after('id')->constrained()->onDelete('cascade');
            }
        });
    }
};
