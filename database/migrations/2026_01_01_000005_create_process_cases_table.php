<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('open'); // open, closed
            $table->timestamps();
        });

        Schema::create('case_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('process_cases')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('process_tasks')->cascadeOnDelete();
            $table->json('data')->nullable();
            // pending = created but not opened, draft = temporary save, completed = finalized & frozen
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_tasks');
        Schema::dropIfExists('process_cases');
    }
};
