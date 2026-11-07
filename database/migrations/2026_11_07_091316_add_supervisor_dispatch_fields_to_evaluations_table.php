<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('supervisor_name')->nullable()->after('token_used');
            $table->string('supervisor_email')->nullable()->after('supervisor_name');
            $table->timestamp('invitation_sent_at')->nullable()->after('supervisor_email');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['supervisor_name', 'supervisor_email', 'invitation_sent_at']);
        });
    }
};
