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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('quote_footer');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('account_name')->nullable()->after('bank_branch');
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('branch_code')->nullable()->after('account_number');
            $table->string('swift_code')->nullable()->after('branch_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_branch',
                'account_name',
                'account_number',
                'branch_code',
                'swift_code',
            ]);
        });
    }
};
