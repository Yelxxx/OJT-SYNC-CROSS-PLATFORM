<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('share_token', 40)->nullable()->unique()->after('logo_path');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('share_token', 40)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
    }
};
