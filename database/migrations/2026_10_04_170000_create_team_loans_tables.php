<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->nullable()->unique();
            $table->foreignId('borrower_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            $table->string('purpose', 180);
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['workspace_id', 'issued_at']);
        });

        Schema::create('team_loan_repayments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_loan_id')->constrained()->restrictOnDelete();
            $table->string('code', 30)->nullable()->unique();
            $table->decimal('amount', 15, 2);
            $table->date('repaid_at');
            $table->string('method', 30);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('received')->index();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['workspace_id', 'repaid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_loan_repayments');
        Schema::dropIfExists('team_loans');
    }
};
