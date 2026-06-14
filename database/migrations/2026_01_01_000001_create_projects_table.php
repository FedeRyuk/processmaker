<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // Per-project MySQL connection used to store the process configuration/data
            $table->string('db_host')->default('127.0.0.1');
            $table->unsignedInteger('db_port')->default(3306);
            $table->string('db_database')->nullable();
            $table->string('db_username')->default('root');
            $table->string('db_password')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
