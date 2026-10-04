<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\IndexProjectInvoiceRequest;
use App\Http\Requests\Api\V1\Projects\CancelProjectInvoiceRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectInvoiceRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectInvoiceRequest;
use App\Models\Projects\ProjectInvoice;
use App\Models\Projects\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProjectInvoiceController extends Controller
{
    public function index(IndexProjectInvoiceRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $invoices = ProjectInvoice::query()->with(['project.customer:id,code,name', 'project.projectType:id,code,name', 'replacement:id,code,supersedes_invoice_id', 'supersedes:id,code'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('billing_period', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($project) => $project->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('project.customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();

        return response()->json($invoices);
    }

    public function store(StoreProjectInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['billing_period'] = $data['billing_cycle'] === 'monthly' ? ($data['billing_period'] ?? null) : null;
        $invoice = DB::transaction(function () use ($data): ProjectInvoice {
            Project::query()->whereKey($data['project_id'])->lockForUpdate()->firstOrFail();
            $alreadyBilled = ProjectInvoice::query()->where('project_id', $data['project_id'])
                ->where('billing_cycle', $data['billing_cycle'])
                ->when($data['billing_cycle'] === 'monthly', fn ($query) => $query->where('billing_period', $data['billing_period']))
                ->whereIn('status', ['draft', 'issued'])->exists();
            if ($alreadyBilled) {
                $field = $data['billing_cycle'] === 'monthly' ? 'billing_period' : 'billing_cycle';
                throw ValidationException::withMessages([$field => 'Project sudah memiliki invoice aktif untuk jenis/periode ini.']);
            }
            $pendingReplacement = ProjectInvoice::query()->where('project_id', $data['project_id'])
                ->where('billing_cycle', $data['billing_cycle'])
                ->when($data['billing_cycle'] === 'monthly', fn ($query) => $query->where('billing_period', $data['billing_period']))
                ->when($data['billing_cycle'] === 'one_time', fn ($query) => $query->whereNull('billing_period'))
                ->where('status', 'cancelled')
                ->whereDoesntHave('replacement')->orderByDesc('id')->first();
            if ($pendingReplacement && (int) ($data['supersedes_invoice_id'] ?? 0) !== (int) $pendingReplacement->id) {
                throw ValidationException::withMessages(['supersedes_invoice_id' => 'Invoice bulan ini harus dibuat sebagai pengganti invoice batal '.$pendingReplacement->code.'.']);
            }
            if (! empty($data['supersedes_invoice_id'])) {
                $previous = ProjectInvoice::query()->lockForUpdate()->findOrFail($data['supersedes_invoice_id']);
                $valid = $previous->status === 'cancelled'
                    && (int) $previous->project_id === (int) $data['project_id']
                    && $previous->billing_cycle === $data['billing_cycle']
                    && $previous->billing_period === $data['billing_period']
                    && ! $previous->replacement()->exists();
                if (! $valid) {
                    throw ValidationException::withMessages(['supersedes_invoice_id' => 'Invoice pengganti harus merujuk invoice batal dari project dan bulan yang sama, yang belum digantikan.']);
                }
            }
            return ProjectInvoice::create($data);
        });
        return response()->json(['invoice' => $invoice->load(['project.customer:id,code,name', 'project.projectType:id,code,name', 'supersedes:id,code'])], Response::HTTP_CREATED);
    }

    public function show(ProjectInvoice $invoice): JsonResponse
    {
        return response()->json(['invoice' => $invoice->load(['project.customer:id,code,name', 'project.projectType:id,code,name', 'replacement:id,code,supersedes_invoice_id', 'supersedes:id,code'])]);
    }

    public function update(UpdateProjectInvoiceRequest $request, ProjectInvoice $invoice): JsonResponse
    {
        abort_unless($invoice->status === 'draft', Response::HTTP_CONFLICT, 'Hanya invoice draft yang dapat diubah.');
        $data = $request->validated();
        DB::transaction(function () use ($invoice, $data): void {
            Project::query()->whereKey($invoice->project_id)->lockForUpdate()->firstOrFail();
            $cycle = $data['billing_cycle'] ?? $invoice->billing_cycle;
            $period = $cycle === 'monthly' ? ($data['billing_period'] ?? $invoice->billing_period) : null;
            if ($invoice->supersedes_invoice_id && ($cycle !== $invoice->billing_cycle || $period !== $invoice->billing_period)) {
                throw ValidationException::withMessages(['billing_period' => 'Jenis dan periode invoice pengganti tidak dapat diubah.']);
            }
            if ($cycle !== $invoice->billing_cycle || $period !== $invoice->billing_period) {
                $alreadyBilled = ProjectInvoice::query()->where('project_id', $invoice->project_id)
                    ->where('billing_cycle', $cycle)
                    ->when($cycle === 'monthly', fn ($query) => $query->where('billing_period', $period))
                    ->whereIn('status', ['draft', 'issued'])->exists();
                if ($alreadyBilled) {
                    throw ValidationException::withMessages(['billing_period' => 'Project sudah memiliki invoice aktif untuk jenis/periode ini.']);
                }
            }
            $data['billing_cycle'] = $cycle;
            $data['billing_period'] = $period;
            $invoice->update($data);
        });
        return response()->json(['invoice' => $invoice->fresh()->load(['project.customer:id,code,name', 'project.projectType:id,code,name', 'supersedes:id,code'])]);
    }

    public function issue(ProjectInvoice $invoice): JsonResponse
    {
        abort_unless($invoice->status === 'draft', Response::HTTP_CONFLICT, 'Hanya invoice draft yang dapat diterbitkan.');
        $invoice->update(['status' => 'issued']);
        return response()->json(['invoice' => $invoice->fresh()->load(['project.customer:id,code,name', 'project.projectType:id,code,name', 'supersedes:id,code'])]);
    }

    public function cancel(CancelProjectInvoiceRequest $request, ProjectInvoice $invoice): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request, $invoice): ProjectInvoice {
            $locked = ProjectInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            abort_unless($locked->status === 'issued', Response::HTTP_CONFLICT, 'Hanya invoice terbit yang dapat dibatalkan.');
            if ($locked->payments()->where('status', 'received')->exists()) {
                throw ValidationException::withMessages(['invoice' => 'Invoice memiliki penerimaan sah. Koreksi seluruh penerimaan terlebih dahulu sebelum membatalkan invoice.']);
            }
            $locked->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->validated('reason'),
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
            ]);
            return $locked;
        });
        return response()->json(['invoice' => $invoice->fresh()->load(['project.customer:id,code,name', 'project.projectType:id,code,name', 'replacement:id,code,supersedes_invoice_id', 'supersedes:id,code'])]);
    }
}
