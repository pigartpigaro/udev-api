<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\IndexProjectExpenseRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectExpenseRequest;
use App\Http\Requests\Api\V1\Projects\VoidProjectExpenseRequest;
use App\Models\Projects\ProjectExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProjectExpenseController extends Controller
{
    public function index(IndexProjectExpenseRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $expenses = ProjectExpense::query()->with(['project:id,code,name', 'recorder:id,name', 'voider:id,name'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('paid_to', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($project) => $project->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('spent_at')->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();

        return response()->json($expenses);
    }

    public function store(StoreProjectExpenseRequest $request): JsonResponse
    {
        $expense = ProjectExpense::create([...$request->validated(), 'status' => 'paid', 'recorded_by' => $request->user()->id]);
        return response()->json(['expense' => $expense->load(['project:id,code,name', 'recorder:id,name'])], Response::HTTP_CREATED);
    }

    public function void(VoidProjectExpenseRequest $request, ProjectExpense $expense): JsonResponse
    {
        $expense = DB::transaction(function () use ($request, $expense): ProjectExpense {
            $locked = ProjectExpense::query()->lockForUpdate()->findOrFail($expense->id);
            abort_unless($locked->status === 'paid', Response::HTTP_CONFLICT, 'Hanya pengeluaran sah yang dapat dikoreksi.');
            $locked->update(['status' => 'voided', 'void_reason' => $request->validated('reason'), 'voided_at' => now(), 'voided_by' => $request->user()->id]);
            return $locked;
        });
        return response()->json(['expense' => $expense->fresh()->load(['project:id,code,name', 'recorder:id,name', 'voider:id,name'])]);
    }
}
