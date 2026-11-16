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
        Schema::table('students', function (Blueprint $table) {
            $table->date('deployment_date')->nullable()->after('cleared_by');
            $table->date('target_end_date')->nullable()->after('deployment_date');
            $table->timestamp('last_nudge_at')->nullable()->after('target_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['deployment_date', 'target_end_date', 'last_nudge_at']);
        });
    }
};
