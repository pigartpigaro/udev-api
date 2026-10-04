<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_invoices', function (Blueprint $table): void {
            $table->string('billing_cycle', 12)->default('monthly')->after('project_id');
        });

        DB::table('project_invoices')->whereNull('billing_period')->update(['billing_period' => DB::raw("DATE_FORMAT(issued_at, '%Y-%m')")]);
        Schema::table('project_invoices', function (Blueprint $table): void {
            $table->string('billing_period', 7)->nullable()->change();
            $table->index(['project_id', 'billing_cycle', 'billing_period'], 'project_invoices_project_cycle_period_index');
        });
    }

    public function down(): void
    {
        if (DB::table('project_invoices')->where('billing_cycle', 'one_time')->exists()) {
            throw new RuntimeException('Cannot remove the billing-cycle field while one-time invoices exist.');
        }

        Schema::table('project_invoices', function (Blueprint $table): void {
            $table->dropIndex('project_invoices_project_cycle_period_index');
            $table->dropColumn('billing_cycle');
            $table->string('billing_period', 7)->nullable(false)->change();
        });
    }
};
