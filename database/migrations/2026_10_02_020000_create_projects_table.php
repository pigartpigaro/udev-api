<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->nullable()->unique();
            $table->string('name');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_type_id')->constrained('project_types')->restrictOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
