<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('invoice_id')->constrained('project_invoices')->restrictOnDelete();
            $table->string('payment_type', 20);
            $table->decimal('amount', 15, 2);
            $table->date('received_at');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 12)->default('received')->index();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
            $table->index(['received_at', 'id']);
        });
    }

    public function down(): void { Schema::dropIfExists('project_payments'); }
};
