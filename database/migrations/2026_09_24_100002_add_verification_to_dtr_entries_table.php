<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->string('verification_status')->default('unverified')->after('checkin_method');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verification_status', 'verified_by', 'verified_at']);
        });
    }
};
