<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_invoices', 'billing_period')) {
            Schema::table('project_invoices', function (Blueprint $table): void {
                $table->string('billing_period', 7)->nullable()->after('project_id');
            });
        }

        DB::table('project_invoices')->whereNull('billing_period')->select(['id', 'issued_at'])->orderBy('id')->chunkById(500, function ($invoices): void {
            foreach ($invoices as $invoice) {
                DB::table('project_invoices')->where('id', $invoice->id)->update([
                    'billing_period' => substr((string) $invoice->issued_at, 0, 7),
                ]);
            }
        });

        Schema::table('project_invoices', function (Blueprint $table): void {
            $table->string('billing_period', 7)->nullable(false)->change();
        });

        if (! Schema::hasIndex('project_invoices', 'project_invoices_project_period_index')) {
            Schema::table('project_invoices', function (Blueprint $table): void {
                $table->index(['project_id', 'billing_period'], 'project_invoices_project_period_index');
            });
        }
        if (Schema::hasIndex('project_invoices', 'project_invoices_project_id_unique', 'unique')) {
            Schema::table('project_invoices', function (Blueprint $table): void {
                $table->dropUnique('project_invoices_project_id_unique');
            });
        }
        Schema::table('project_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_invoices', 'supersedes_invoice_id')) {
                $table->foreignId('supersedes_invoice_id')->nullable()->unique()->constrained('project_invoices')->restrictOnDelete();
            }
            if (! Schema::hasColumn('project_invoices', 'cancellation_reason')) $table->text('cancellation_reason')->nullable();
            if (! Schema::hasColumn('project_invoices', 'cancelled_at')) $table->timestamp('cancelled_at')->nullable();
            if (! Schema::hasColumn('project_invoices', 'cancelled_by')) $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $hasMultipleInvoices = DB::table('project_invoices')
            ->select('project_id')
            ->groupBy('project_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasMultipleInvoices) {
            throw new RuntimeException('Cannot restore the one-invoice-per-project constraint while multiple invoices exist.');
        }

        Schema::table('project_invoices', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_invoice_id']);
            $table->dropUnique(['supersedes_invoice_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex('project_invoices_project_period_index');
            $table->dropColumn(['supersedes_invoice_id', 'cancellation_reason', 'cancelled_at', 'cancelled_by']);
            $table->dropColumn('billing_period');
            $table->unique('project_id');
        });
    }
};
