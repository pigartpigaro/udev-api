<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\IndexProjectPaymentRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectPaymentRequest;
use App\Http\Requests\Api\V1\Projects\VoidProjectPaymentRequest;
use App\Models\Projects\ProjectInvoice;
use App\Models\Projects\ProjectPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ProjectPaymentController extends Controller
{
    public function show(ProjectPayment $payment): JsonResponse
    {
        return response()->json(['payment' => $payment->load(['invoice.project.customer:id,code,name', 'receiver:id,name', 'voider:id,name'])]);
    }

    public function index(IndexProjectPaymentRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $payments = ProjectPayment::query()->with(['invoice.project.customer:id,code,name', 'receiver:id,name', 'voider:id,name'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($invoice) => $invoice->where('code', 'like', "%{$search}%")
                            ->orWhereHas('project', fn ($project) => $project->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                            ->orWhereHas('project.customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")));
                });
            })
            ->orderByDesc('received_at')->orderByDesc('id')->paginate($filters['per_page'] ?? 20)->withQueryString();

        return response()->json($payments);
    }

    public function eligibleInvoices(IndexProjectPaymentRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $invoices = ProjectInvoice::query()->with(['project.customer:id,code,name'])
            ->withSum(['payments as received_total' => fn ($query) => $query->where('status', 'received')], 'amount')
            ->where('status', 'issued')
            ->whereRaw('project_invoices.amount > (select coalesce(sum(project_payments.amount), 0) from project_payments where project_payments.workspace_id = project_invoices.workspace_id and project_payments.invoice_id = project_invoices.id and project_payments.status = ?)', ['received'])
            ->when($search, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($project) => $project->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('project.customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            }))
            ->orderByDesc('id')->paginate($request->validated('per_page') ?? 20)->through(function (ProjectInvoice $invoice): array {
                $received = self::toCents($invoice->received_total ?? '0');
                $remaining = max(0, self::toCents($invoice->amount) - $received);
                return ['id' => $invoice->id, 'code' => $invoice->code, 'amount' => $invoice->amount, 'received_total' => self::fromCents($received), 'remaining_amount' => self::fromCents($remaining), 'project' => $invoice->project];
            });

        return response()->json($invoices);
    }

    public function store(StoreProjectPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $payment = DB::transaction(function () use ($data, $request): ProjectPayment {
            $invoice = ProjectInvoice::query()->lockForUpdate()->findOrFail($data['invoice_id']);
            if ($invoice->status !== 'issued') {
                throw ValidationException::withMessages(['invoice_id' => 'Pembayaran hanya dapat dicatat untuk invoice yang sudah terbit.']);
            }
            $received = self::toCents($invoice->payments()->where('status', 'received')->sum('amount'));
            $remaining = max(0, self::toCents($invoice->amount) - $received);
            if (self::toCents($data['amount']) > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan Rp '.number_format($remaining / 100, 2, ',', '.').'.']);
            }
            return ProjectPayment::create([...$data, 'status' => 'received', 'received_by' => $request->user()->id]);
        });

        return response()->json(['payment' => $payment->load(['invoice.project.customer:id,code,name', 'receiver:id,name'])], Response::HTTP_CREATED);
    }

    public function void(VoidProjectPaymentRequest $request, ProjectPayment $payment): JsonResponse
    {
        $payment = DB::transaction(function () use ($request, $payment): ProjectPayment {
            $locked = ProjectPayment::query()->lockForUpdate()->findOrFail($payment->id);
            abort_unless($locked->status === 'received', Response::HTTP_CONFLICT, 'Hanya penerimaan sah yang dapat dikoreksi.');
            $locked->update(['status' => 'voided', 'void_reason' => $request->validated('reason'), 'voided_at' => now(), 'voided_by' => $request->user()->id]);
            return $locked;
        });
        return response()->json(['payment' => $payment->fresh()->load(['invoice.project.customer:id,code,name', 'receiver:id,name', 'voider:id,name'])]);
    }

    private static function toCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private static function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
