<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->boolean('break_deducted')->default(false)->after('hours');
            $table->boolean('is_auto_closed')->default(false)->after('break_deducted');
        });
    }

    public function down(): void
    {
        Schema::table('dtr_entries', function (Blueprint $table) {
            $table->dropColumn(['break_deducted', 'is_auto_closed']);
        });
    }
};
