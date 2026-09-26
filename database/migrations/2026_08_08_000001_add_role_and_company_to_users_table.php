<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index()->after('email'); // student | coordinator | hr
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('department')->nullable()->after('is_active'); // coordinator department
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'department']);
        });
    }
};
