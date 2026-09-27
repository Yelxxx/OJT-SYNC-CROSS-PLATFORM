<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // pending: applied via public form, awaiting admin approval.
            // approved: partner company, HR can log in and post internships.
            // rejected: application denied by the admin.
            $table->string('status', 20)->default('approved')->index()->after('is_active');
            $table->string('contact_person')->nullable()->after('contact_email');
            $table->string('rejection_reason', 500)->nullable()->after('description');
        });

        // Existing companies were all created by trusted seeds/coordinators,
        // so they start as approved; is_active already covers suspension.
        DB::table('companies')->where('is_active', true)->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['status', 'contact_person', 'rejection_reason']);
        });
    }
};
