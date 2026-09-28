<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The offline sync queue (CROSS_PLATFORM_PLAN.md Phase 2) was removed: all
 * DTR and journal actions are plain online submissions, so the client_uuid
 * idempotency keys have no writer anymore. Existing values are dropped with
 * the columns (they were replay metadata, not user data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });

        Schema::table('e_journals', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn('client_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');
        });

        Schema::table('e_journals', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');
        });
    }
};
