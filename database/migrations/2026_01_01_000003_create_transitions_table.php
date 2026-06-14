<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_task_id')->constrained('process_tasks')->cascadeOnDelete();
            $table->foreignId('to_task_id')->constrained('process_tasks')->cascadeOnDelete();
            // Conditions on field values for which this arrow is valid:
            // { "logic": "AND|OR", "rules": [ { "field": "...", "operator": "==", "value": "..." } ] }
            $table->json('conditions')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transitions');
    }
};
