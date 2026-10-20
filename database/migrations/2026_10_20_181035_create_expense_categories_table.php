<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The categories the expense form offered when they were hard-coded. */
    private const DEFAULTS = [
        'fuel' => 'Fuel',
        'maintenance' => 'Maintenance',
        'repairs' => 'Repairs',
        'tolls' => 'Tolls',
        'insurance' => 'Insurance',
        'licenses' => 'Licenses',
        'wages' => 'Driver wages',
        'other' => 'Other',
    ];

    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('value')->unique(); // stored in expenses.category
            $table->string('label');
            $table->timestamps();
        });

        $now = now();
        DB::table('expense_categories')->insert(collect(self::DEFAULTS)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all());

        // The MySQL ENUM only held the original values (not even 'licenses' or 'wages'), so new categories need a plain string.
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category')->default('fuel')->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
