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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('moa_status')->default('active')->after('status'); // active, pending_renewal, expired, none
            $table->date('moa_valid_until')->nullable()->after('moa_status');
            $table->string('moa_file_path')->nullable()->after('moa_valid_until');
            $table->string('moa_notes', 500)->nullable()->after('moa_file_path');
        });

        Schema::table('intern_checklist_items', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('is_complete'); // pending, submitted, approved, rejected
            $table->text('rejection_reason')->nullable()->after('notes');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_cleared_for_deployment')->default(false)->after('status');
            $table->timestamp('cleared_at')->nullable()->after('is_cleared_for_deployment');
            $table->foreignId('cleared_by')->nullable()->after('cleared_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cleared_by');
            $table->dropColumn(['is_cleared_for_deployment', 'cleared_at']);
        });

        Schema::table('intern_checklist_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['moa_status', 'moa_valid_until', 'moa_file_path', 'moa_notes']);
        });
    }
};
