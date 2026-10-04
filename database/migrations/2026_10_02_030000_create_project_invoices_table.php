<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('project_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
