<?php

namespace App\Services\Finance;

use App\Tenancy\WorkspaceContext;
use Illuminate\Support\Facades\DB;

class WorkspaceCashPosition
{
    public function currentCents(): int
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $received = DB::table('project_payments as payments')
            ->join('project_invoices as invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->where('payments.workspace_id', $workspaceId)->where('invoices.workspace_id', $workspaceId)
            ->where('payments.status', 'received')->where('invoices.status', 'issued')->sum('payments.amount');
        $repayments = DB::table('team_loan_repayments')->where('workspace_id', $workspaceId)->where('status', 'received')->sum('amount');
        $expenses = DB::table('project_expenses')->where('workspace_id', $workspaceId)->where('status', 'paid')->sum('amount');
        $loans = DB::table('team_loans')->where('workspace_id', $workspaceId)->where('status', '!=', 'voided')->sum('amount');

        return self::cents($received) + self::cents($repayments) - self::cents($expenses) - self::cents($loans);
    }

    public static function cents(string|int|float|null $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) ($amount ?? '0'), 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    public static function amount(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
