<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->integer('pos_x')->default(40);
            $table->integer('pos_y')->default(40);
            $table->timestamps();
        });

        Schema::create('task_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('process_tasks')->cascadeOnDelete();
            $table->string('name');          // machine name / key
            $table->string('label');         // human label
            $table->string('type')->default('text'); // text, textarea, number, date, select, checkbox, table
            $table->json('options')->nullable();  // select options or table columns definition
            $table->json('config')->nullable();   // required, readonly, behaviour, etc.
            // Read mapping (load value, or default when absent)
            $table->string('read_db')->nullable();
            $table->string('read_table')->nullable();
            $table->string('read_column')->nullable();
            $table->text('default_value')->nullable();
            // Write mapping (save value)
            $table->string('write_db')->nullable();
            $table->string('write_table')->nullable();
            $table->string('write_column')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_fields');
        Schema::dropIfExists('process_tasks');
    }
};
