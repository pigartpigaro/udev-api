<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\ProjectFinancialReportRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Tenancy\WorkspaceContext;
use App\Services\Finance\WorkspaceCashPosition;

class ProjectFinancialReportController extends Controller
{
    public function __invoke(ProjectFinancialReportRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $projectId = isset($filters['project_id']) ? (int) $filters['project_id'] : null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $workspaceId = app(WorkspaceContext::class)->id();

        $invoices = DB::table('project_invoices')->select('project_id')->selectRaw('SUM(amount) AS total')
            ->where('workspace_id', $workspaceId)->where('status', 'issued')->when($projectId, fn (Builder $query) => $query->where('project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('issued_at', '<=', $dateTo))->groupBy('project_id');

        $receipts = DB::table('project_payments as payments')
            ->join('project_invoices as invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->select('invoices.project_id')->selectRaw('SUM(payments.amount) AS total')
            ->where('payments.workspace_id', $workspaceId)->where('invoices.workspace_id', $workspaceId)
            ->where('payments.status', 'received')->where('invoices.status', 'issued')
            ->when($projectId, fn (Builder $query) => $query->where('invoices.project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('payments.received_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('payments.received_at', '<=', $dateTo))->groupBy('invoices.project_id');

        $expenses = DB::table('project_expenses')->select('project_id')->selectRaw('SUM(amount) AS total')
            ->where('workspace_id', $workspaceId)->where('status', 'paid')->whereNotNull('project_id')
            ->when($projectId, fn (Builder $query) => $query->where('project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('spent_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('spent_at', '<=', $dateTo))->groupBy('project_id');

        $paymentsPerInvoice = DB::table('project_payments')->select('invoice_id')->selectRaw('SUM(amount) AS received_total')
            ->where('workspace_id', $workspaceId)->where('status', 'received')->groupBy('invoice_id');
        $outstanding = DB::table('project_invoices as invoices')
            ->leftJoinSub($paymentsPerInvoice, 'receipts', fn ($join) => $join->on('receipts.invoice_id', '=', 'invoices.id'))
            ->select('invoices.project_id')
            ->selectRaw('SUM(CASE WHEN invoices.amount > COALESCE(receipts.received_total, 0) THEN invoices.amount - COALESCE(receipts.received_total, 0) ELSE 0 END) AS total')
            ->where('invoices.workspace_id', $workspaceId)->where('invoices.status', 'issued')->when($projectId, fn (Builder $query) => $query->where('invoices.project_id', $projectId))
            ->groupBy('invoices.project_id');

        $projects = DB::table('projects')
            ->leftJoinSub($invoices, 'invoice_totals', fn ($join) => $join->on('invoice_totals.project_id', '=', 'projects.id'))
            ->leftJoinSub($receipts, 'receipt_totals', fn ($join) => $join->on('receipt_totals.project_id', '=', 'projects.id'))
            ->leftJoinSub($expenses, 'expense_totals', fn ($join) => $join->on('expense_totals.project_id', '=', 'projects.id'))
            ->leftJoinSub($outstanding, 'outstanding_totals', fn ($join) => $join->on('outstanding_totals.project_id', '=', 'projects.id'))
            ->select('projects.id', 'projects.code', 'projects.name')
            ->selectRaw('COALESCE(invoice_totals.total, 0) AS invoice_issued')
            ->selectRaw('COALESCE(receipt_totals.total, 0) AS received')
            ->selectRaw('COALESCE(expense_totals.total, 0) AS expenses')
            ->selectRaw('COALESCE(outstanding_totals.total, 0) AS outstanding')
            ->where('projects.workspace_id', $workspaceId)
            ->when($projectId, fn (Builder $query) => $query->where('projects.id', $projectId))
            ->orderBy('projects.name')->orderBy('projects.id')
            ->paginate($filters['per_page'] ?? 50)->withQueryString();

        $invoiceTotal = self::toCents(DB::table('project_invoices')->where('workspace_id', $workspaceId)->where('status', 'issued')
            ->when($projectId, fn (Builder $query) => $query->where('project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('issued_at', '<=', $dateTo))->sum('amount'));

        $receivedTotal = self::toCents(DB::table('project_payments as payments')
            ->join('project_invoices as invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.workspace_id', $workspaceId)->where('invoices.workspace_id', $workspaceId)
            ->where('payments.status', 'received')->where('invoices.status', 'issued')
            ->when($projectId, fn (Builder $query) => $query->where('invoices.project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('payments.received_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('payments.received_at', '<=', $dateTo))->sum('payments.amount'));

        $projectExpenseTotal = self::toCents(DB::table('project_expenses')->where('workspace_id', $workspaceId)->where('status', 'paid')->whereNotNull('project_id')
            ->when($projectId, fn (Builder $query) => $query->where('project_id', $projectId))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('spent_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('spent_at', '<=', $dateTo))->sum('amount'));

        $operationalExpenseTotal = $projectId ? 0 : self::toCents(DB::table('project_expenses')->where('workspace_id', $workspaceId)->where('status', 'paid')->whereNull('project_id')
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('spent_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('spent_at', '<=', $dateTo))->sum('amount'));

        $outstandingTotal = self::toCents(DB::table('project_invoices as invoices')
            ->leftJoinSub($paymentsPerInvoice, 'receipts', fn ($join) => $join->on('receipts.invoice_id', '=', 'invoices.id'))
            ->where('invoices.workspace_id', $workspaceId)->where('invoices.status', 'issued')->when($projectId, fn (Builder $query) => $query->where('invoices.project_id', $projectId))
            ->selectRaw('COALESCE(SUM(CASE WHEN invoices.amount > COALESCE(receipts.received_total, 0) THEN invoices.amount - COALESCE(receipts.received_total, 0) ELSE 0 END), 0) AS total')->value('total'));

        $teamLoansIssued = $projectId ? 0 : self::toCents(DB::table('team_loans')->where('workspace_id', $workspaceId)->where('status', '!=', 'voided')
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('issued_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('issued_at', '<=', $dateTo))->sum('amount'));
        $teamLoanRepayments = $projectId ? 0 : self::toCents(DB::table('team_loan_repayments')->where('workspace_id', $workspaceId)->where('status', 'received')
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('repaid_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('repaid_at', '<=', $dateTo))->sum('amount'));
        $allLoans = DB::table('team_loans')->where('workspace_id', $workspaceId)->where('status', '!=', 'voided')->sum('amount');
        $allRepayments = DB::table('team_loan_repayments')->where('workspace_id', $workspaceId)->where('status', 'received')->sum('amount');
        $teamLoansOutstanding = $projectId ? 0 : self::toCents($allLoans) - self::toCents($allRepayments);

        $projects->setCollection($projects->getCollection()->map(function (object $project): array {
            $received = self::toCents($project->received);
            $expenses = self::toCents($project->expenses);
            return [
                'id' => (int) $project->id, 'code' => $project->code, 'name' => $project->name,
                'invoice_issued' => self::fromCents(self::toCents($project->invoice_issued)),
                'received' => self::fromCents($received), 'expenses' => self::fromCents($expenses),
                'cash_flow' => self::fromCents($received - $expenses),
                'outstanding' => self::fromCents(self::toCents($project->outstanding)),
            ];
        }));

        return response()->json([
            'summary' => [
                'invoice_issued' => self::fromCents($invoiceTotal),
                'received' => self::fromCents($receivedTotal),
                'project_expenses' => self::fromCents($projectExpenseTotal),
                'operational_expenses' => self::fromCents($operationalExpenseTotal),
                'expenses' => self::fromCents($projectExpenseTotal + $operationalExpenseTotal),
                'cash_flow' => self::fromCents($receivedTotal + $teamLoanRepayments - $projectExpenseTotal - $operationalExpenseTotal - $teamLoansIssued),
                'outstanding' => self::fromCents($outstandingTotal),
                'team_loans_issued' => self::fromCents($teamLoansIssued),
                'team_loan_repayments' => self::fromCents($teamLoanRepayments),
                'team_loans_outstanding' => self::fromCents(max(0, $teamLoansOutstanding)),
                'cash_available' => $projectId ? '0.00' : WorkspaceCashPosition::amount(app(WorkspaceCashPosition::class)->currentCents()),
            ],
            'projects' => $projects,
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'project_id' => $projectId],
        ]);
    }

    private static function toCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private static function fromCents(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $amount = abs($amount);
        return $sign.intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
