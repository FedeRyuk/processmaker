<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('db_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('db_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('db_table_id')->constrained('db_tables')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('VARCHAR'); // VARCHAR, INT, TEXT, DATE, DATETIME, DECIMAL, BOOLEAN
            $table->string('length')->nullable();
            $table->boolean('nullable')->default(true);
            $table->string('default')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('auto_increment')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('db_columns');
        Schema::dropIfExists('db_tables');
    }
};
