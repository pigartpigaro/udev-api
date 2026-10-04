<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\StoreTeamLoanRepaymentRequest;
use App\Http\Requests\Api\V1\Projects\StoreTeamLoanRequest;
use App\Models\Finance\TeamLoan;
use App\Models\Finance\TeamLoanRepayment;
use App\Services\Finance\WorkspaceCashPosition;
use App\Tenancy\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TeamLoanController extends Controller
{
    public function index(): JsonResponse
    {
        $loans = TeamLoan::query()->with(['borrower:id,name,username', 'recorder:id,name', 'voider:id,name'])
            ->withSum(['repayments as repaid_total' => fn ($query) => $query->where('status', 'received')], 'amount')
            ->when(request('search'), function ($query, string $search): void {
                $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('borrower', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")));
            })->orderByDesc('issued_at')->orderByDesc('id')->paginate(min((int) request('per_page', 20), 100))->withQueryString();
        $loans->getCollection()->transform(function (TeamLoan $loan): TeamLoan {
            $remaining = WorkspaceCashPosition::cents($loan->amount) - WorkspaceCashPosition::cents($loan->repaid_total);
            $loan->setAttribute('remaining_amount', WorkspaceCashPosition::amount(max(0, $remaining)));
            return $loan;
        });
        return response()->json($loans);
    }

    public function members(): JsonResponse
    {
        $members = DB::table('workspace_user')->join('users', 'users.id', '=', 'workspace_user.user_id')
            ->where('workspace_user.workspace_id', app(WorkspaceContext::class)->id())
            ->select('users.id', 'users.name', 'users.username')->orderBy('users.name')->get();
        return response()->json(['data' => $members]);
    }

    public function summary(WorkspaceCashPosition $cashPosition): JsonResponse
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        $loanTotal = DB::table('team_loans')->where('workspace_id', $workspaceId)->where('status', '!=', 'voided')->sum('amount');
        $repaidTotal = DB::table('team_loan_repayments')->where('workspace_id', $workspaceId)->where('status', 'received')->sum('amount');
        $outstanding = WorkspaceCashPosition::cents($loanTotal) - WorkspaceCashPosition::cents($repaidTotal);
        $repaidPerLoan = DB::table('team_loan_repayments')->select('team_loan_id')->selectRaw('SUM(amount) AS total')
            ->where('workspace_id', $workspaceId)->where('status', 'received')->groupBy('team_loan_id');
        $balances = DB::table('team_loans as loans')->join('users', 'users.id', '=', 'loans.borrower_id')
            ->leftJoinSub($repaidPerLoan, 'repayments', fn ($join) => $join->on('repayments.team_loan_id', '=', 'loans.id'))
            ->where('loans.workspace_id', $workspaceId)->whereIn('loans.status', ['open', 'settled'])
            ->select('users.id', 'users.name', 'users.username')
            ->selectRaw('SUM(loans.amount - COALESCE(repayments.total, 0)) AS outstanding')
            ->groupBy('users.id', 'users.name', 'users.username')->orderBy('users.name')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => $row->name, 'username' => $row->username, 'outstanding' => WorkspaceCashPosition::amount(WorkspaceCashPosition::cents($row->outstanding))])
            ->filter(fn (array $row) => WorkspaceCashPosition::cents($row['outstanding']) > 0)->values();
        return response()->json([
            'cash_available' => WorkspaceCashPosition::amount($cashPosition->currentCents()),
            'loans_disbursed' => WorkspaceCashPosition::amount(WorkspaceCashPosition::cents($loanTotal)),
            'repayments_received' => WorkspaceCashPosition::amount(WorkspaceCashPosition::cents($repaidTotal)),
            'outstanding' => WorkspaceCashPosition::amount(max(0, $outstanding)),
            'borrower_balances' => $balances,
        ]);
    }

    public function store(StoreTeamLoanRequest $request, WorkspaceCashPosition $cashPosition): JsonResponse
    {
        $loan = DB::transaction(function () use ($request, $cashPosition): TeamLoan {
            $this->lockWorkspace();
            $amountCents = WorkspaceCashPosition::cents($request->validated('amount'));
            abort_if($amountCents > $cashPosition->currentCents(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Saldo kas tersedia tidak cukup untuk mencatat kasbon ini.');
            return TeamLoan::create([...$request->validated(), 'status' => 'open', 'recorded_by' => $request->user()->id])
                ->load(['borrower:id,name,username', 'recorder:id,name']);
        });
        return response()->json(['loan' => $loan], Response::HTTP_CREATED);
    }

    public function repay(StoreTeamLoanRepaymentRequest $request, TeamLoan $loan): JsonResponse
    {
        $repayment = DB::transaction(function () use ($request, $loan): TeamLoanRepayment {
            $this->lockWorkspace();
            $lockedLoan = TeamLoan::query()->lockForUpdate()->findOrFail($loan->id);
            abort_unless($lockedLoan->status === 'open', Response::HTTP_CONFLICT, 'Kasbon ini sudah lunas atau dibatalkan.');
            $received = $lockedLoan->repayments()->where('status', 'received')->sum('amount');
            $remaining = WorkspaceCashPosition::cents($lockedLoan->amount) - WorkspaceCashPosition::cents($received);
            $amount = WorkspaceCashPosition::cents($request->validated('amount'));
            abort_if($amount > $remaining, Response::HTTP_UNPROCESSABLE_ENTITY, 'Nominal pembayaran melebihi sisa kasbon.');
            $repayment = $lockedLoan->repayments()->create([...$request->validated(), 'status' => 'received', 'recorded_by' => $request->user()->id, 'workspace_id' => app(WorkspaceContext::class)->id()]);
            if ($amount === $remaining) $lockedLoan->update(['status' => 'settled']);
            return $repayment->load('recorder:id,name');
        });
        return response()->json(['repayment' => $repayment], Response::HTTP_CREATED);
    }

    public function voidLoan(TeamLoan $loan): JsonResponse
    {
        $data = request()->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $loan = DB::transaction(function () use ($loan, $data): TeamLoan {
            $this->lockWorkspace();
            $locked = TeamLoan::query()->lockForUpdate()->findOrFail($loan->id);
            abort_unless($locked->status === 'open', Response::HTTP_CONFLICT, 'Hanya kasbon aktif yang dapat dikoreksi.');
            abort_if($locked->repayments()->where('status', 'received')->exists(), Response::HTTP_CONFLICT, 'Kasbon yang sudah memiliki pembayaran tidak dapat dibatalkan. Koreksi pembayaran terlebih dahulu.');
            $locked->update(['status' => 'voided', 'void_reason' => $data['reason'], 'voided_at' => now(), 'voided_by' => request()->user()->id]);
            return $locked->fresh()->load(['borrower:id,name,username', 'voider:id,name']);
        });
        return response()->json(['loan' => $loan]);
    }

    public function voidRepayment(TeamLoan $loan, TeamLoanRepayment $repayment): JsonResponse
    {
        $data = request()->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $repayment = DB::transaction(function () use ($loan, $repayment, $data): TeamLoanRepayment {
            $this->lockWorkspace();
            $lockedLoan = TeamLoan::query()->lockForUpdate()->findOrFail($loan->id);
            $locked = TeamLoanRepayment::query()->lockForUpdate()->findOrFail($repayment->id);
            abort_unless($locked->team_loan_id === $lockedLoan->id && $locked->status === 'received', Response::HTTP_CONFLICT, 'Pembayaran kasbon tidak ditemukan atau sudah dikoreksi.');
            $locked->update(['status' => 'voided', 'void_reason' => $data['reason'], 'voided_at' => now(), 'voided_by' => request()->user()->id]);
            $lockedLoan->update(['status' => 'open']);
            return $locked->fresh()->load('voider:id,name');
        });
        return response()->json(['repayment' => $repayment]);
    }

    public function show(TeamLoan $loan): JsonResponse
    {
        return response()->json(['loan' => $loan->load(['borrower:id,name,username', 'recorder:id,name', 'voider:id,name', 'repayments' => fn ($query) => $query->with(['recorder:id,name', 'voider:id,name'])->orderByDesc('repaid_at')->orderByDesc('id')])]);
    }

    private function lockWorkspace(): void
    {
        DB::table('workspaces')->where('id', app(WorkspaceContext::class)->id())->lockForUpdate()->first();
    }
}
