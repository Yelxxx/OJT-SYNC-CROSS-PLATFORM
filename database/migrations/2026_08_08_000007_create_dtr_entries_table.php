<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dtr_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->decimal('hours', 5, 2)->default(0);
            $table->text('task_summary')->nullable();
            $table->string('checkin_method')->default('manual'); // manual | qr | geo
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dtr_entries');
    }
};
