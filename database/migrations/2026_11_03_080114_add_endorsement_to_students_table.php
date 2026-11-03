<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('endorsed_company_id')->nullable()->after('company_id')->constrained('companies')->nullOnDelete();
            $table->text('endorsement_note')->nullable()->after('endorsed_company_id');
            $table->timestamp('endorsed_at')->nullable()->after('endorsement_note');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('endorsed_company_id');
            $table->dropColumn(['endorsement_note', 'endorsed_at']);
        });
    }
};
