<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Offline writes replayed from the client queue carry a client-generated
     * UUID. The unique indexes make replays a no-op (unique violation caught
     * by the sync endpoints) instead of duplicate DTR entries or journals.
     */
    public function up(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');
        });

        Schema::table('e_journals', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
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
};
