<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_checklist_items', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('completed_at');
            $table->string('file_name')->nullable()->after('file_path');
            $table->timestamp('submitted_at')->nullable()->after('file_name');
            $table->text('notes')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('intern_checklist_items', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_name', 'submitted_at', 'notes']);
        });
    }
};
