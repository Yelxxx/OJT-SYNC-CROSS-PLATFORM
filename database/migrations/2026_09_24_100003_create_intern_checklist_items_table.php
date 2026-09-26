<?php

use Database\Seeders\InternChecklistDefaultsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_complete')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // Seed default onboarding items for every already-hired intern.
        Artisan::call('db:seed', [
            '--class' => InternChecklistDefaultsSeeder::class,
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_checklist_items');
    }
};
